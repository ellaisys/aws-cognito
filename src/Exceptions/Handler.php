<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Throwable;
use PDOException;
use Psr\Log\LogLevel;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;

use Ellaisys\Cognito\Services\JsonResponseService;
use Ellaisys\Cognito\Contracts\ExceptionContract;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class Handler extends ExceptionHandler
{
    /**
     * @var array<string, mixed>
     */
    protected $format;

    /**
     * @var \Modules\Core\Services\JsonResponseService
     */
    protected $response;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        PDOException::class => LogLevel::CRITICAL,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Default constructor.
     */
    public function __construct(
        JsonResponseService $response, $format)
    {
        $this->response = $response;
        $this->format = $format;

        parent::__construct(app());
    }

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     *
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception): void
    {
        $systemErrorCode = null;
        $systemErrorMsg = null;

        // Handle AWS Cognito exceptions to extract more user-friendly messages
        $parentError = $exception->getPrevious();
        if ($parentError instanceof CognitoIdentityProviderException) {
            $systemErrorCode = $parentError->getAwsErrorCode();
            $systemErrorMsg = $parentError->getAwsErrorMessage();
        }

        // Prepare error data for logging
        $errorData = [
            'message'        => $exception->getMessage(),
            'system_code'    => $systemErrorCode,
            'system_message' => $systemErrorMsg,
            'execution_time' => number_format(microtime(true) - LARAVEL_START, 4),
            'ip'             => request()->ip(),
        ];

        //Add system messages when in debug mode
        if (config('app.debug')) {
            $errorData['debug'] = array_merge($errorData['debug'] ?? [], [
                'exception' => get_class($exception),
                'trace' => collect($exception->getTrace())->take(3),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'method'    => request()->method(),
                'input'     => request()->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                ]),
            ]);
        } //End if

        // Log the error with detailed information
        Log::error($exception->getMessage(), $errorData);

        parent::report($exception);
    } //Function ends

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Generic exception report
        $this->reportable(function (Throwable $e): void {
            // You can log to an external service (Sentry, Bugsnag, etc.)
            Log::error('Exception reported: ' . $e->getMessage());
        });

        // Handle API exceptions gracefully
        $this->renderable(function (Throwable $e, $request) {
            return $this->handleException($e, $request);
        });
    } //Function ends

    /**
     * Handle exceptions and return appropriate responses.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    protected function handleException(Throwable $e, $request): mixed
    {
        $isRedirectToLogin = false;
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $errorMessage = 'Something went wrong. Please try again later.';
        $errorKey = '';

        if ($e instanceof AwsCognitoException) { //400
            list($statusCode, $errorMessage, $errorKey) = $this->handleAwsCognitoException($e);
        } elseif ($e instanceof ValidationException) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY; //422
            $errorMessage = 'Data validation error';
            $errorKey = $e->getMessage();
        } elseif ($e instanceof ModelNotFoundException) {
            $statusCode = Response::HTTP_BAD_REQUEST; //400
            $errorMessage = 'Resource not found.';
        } elseif (($e instanceof AuthenticationException) ||
            ($e instanceof UnauthorizedHttpException) ||
            ($e instanceof InvalidUserException) ||
            ($e instanceof NoTokenException) ||
            ($e instanceof InvalidTokenException)) {
            $statusCode = Response::HTTP_UNAUTHORIZED; //401
            $errorMessage = 'Unauthenticated.';
            $errorKey = $e->getMessage();
            $isRedirectToLogin = true;
        } elseif ($e instanceof AccessDeniedHttpException) {
            $statusCode = Response::HTTP_FORBIDDEN; //403
            $errorMessage = 'You do not have permission to perform this action.';
        } elseif (($e instanceof NotFoundHttpException) ||
            ($e instanceof HttpException)) {
            $statusCode = Response::HTTP_BAD_REQUEST; //400
            $errorMessage = $e->getMessage();
        } else {
            if (config('app.debug')) {
                // Show detailed info in debug mode
                return response()->json([
                    'error' => $e->getMessage(),
                    'trace' => collect($e->getTrace())->take(3),
                ], 500);
            }
        }

        // Build the response based on request type (API vs Web)
        return $this->responseBuilder(
            $e, $request, $statusCode,
            $errorMessage, $errorKey,
            $isRedirectToLogin
        );
    } //Function ends

    /**
     * Handle AWS Cognito exceptions and map them to appropriate status codes and messages.
     *
     * @param  \Exception  $e
     *
     * @return array
     */
    protected function handleAwsCognitoException(Exception $e): array
    {
        $statusCode = Response::HTTP_BAD_REQUEST; //400
        switch ($e->getMessage()) {
            case AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED:
                $statusCode = Response::HTTP_UNAUTHORIZED; //401
                $errorMessage = 'User authentication error';
                $errorKey = AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED;
                break;

            case AwsCognitoException::COGNITO_AUTH_USER_RESET_PASS:
                $errorMessage = 'User password reset error';
                $errorKey = AwsCognitoException::COGNITO_AUTH_USER_RESET_PASS;
                break;

            case AwsCognitoException::COGNITO_AUTH_USERNAME_EXISTS:
                $errorMessage = 'User already exists';
                $errorKey = AwsCognitoException::COGNITO_AUTH_USERNAME_EXISTS;
                break;

            case AwsCognitoException::COGNITO_AUTH_CODE_INVALID:
                $errorMessage = 'Invalid confirmation code';
                $errorKey = AwsCognitoException::COGNITO_AUTH_CODE_INVALID;
                break;

            case AwsCognitoException::COGNITO_AUTH_POOL_CONFIG_INVALID:
                $errorMessage = 'Cognito pool configuration error';
                $errorKey = AwsCognitoException::COGNITO_AUTH_POOL_CONFIG_INVALID;
                break;

            default:
                $errorMessage = $e->getMessage();
                $errorKey = 'ERROR_COGNITO_DEFAULT';
                break;
        } //End Switch

        return [$statusCode, $errorMessage, $errorKey];
    } //Function ends

    /**
     * Handle exceptions and return appropriate responses based on request type (API vs Web).
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $statusCode
     * @param  string  $message
     * @param  string|null  $errorKey
     * @param  bool  $isRedirectToLogin
     *
     * @return mixed
     */
    protected function responseBuilder(Throwable $e, $request,
        $statusCode,
        string $message='An error occurred',
        ?string $errorKey = null,
        bool $isRedirectToLogin = false): mixed
    {
        if ($request->isJson() || $request->wantsJson() || $request->expectsJson()) {
            // API Response
            return $this->JsonResponseBuilder(
                $e, $request, $statusCode,
                $message, $errorKey
            );
        } else {
            // Web Response
            return $this->WebResponseBuilder(
                $e, $request, $statusCode,
                $message, $errorKey,
                $isRedirectToLogin
            );
        }
    } //Function ends

    /**
     * Handle exceptions and return appropriate JSON responses.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $statusCode
     * @param  string  $message
     * @param  string|null  $errorKey
     *
     * @return mixed
     */
    protected function JsonResponseBuilder(Throwable $e, $request,
        $statusCode,
        string $message,
        string $errorKey = null): mixed
    {
        return $this->response->fail(
            $e,
            [],
            $statusCode,
            $message,
            $errorKey
        );
    } //Function ends

    /**
     * Handle exceptions and return appropriate web responses (redirects with errors).
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $statusCode
     * @param  string  $message
     * @param  string|null  $errorKey
     * @param  bool  $isRedirectToLogin
     *
     * @return mixed
     */
    protected function WebResponseBuilder(Throwable $e, $request,
        $statusCode,
        string $message,
        string $errorKey = null,
        bool $isRedirectToLogin = false): mixed
    {
        $systemErrorMsg = (!$isRedirectToLogin)?$e->getMessage():$message;

        // Handle AWS Cognito exceptions to extract more user-friendly messages
        $parentError = $e->getPrevious();
        if ($parentError instanceof CognitoIdentityProviderException) {
            $systemErrorMsg = $parentError->getAwsErrorMessage();
        }

        // Validation errors will have a different structure, so we handle them separately
        $errors=($e instanceof ValidationException)?$e->errors():['error' => $systemErrorMsg];

        if ($isRedirectToLogin) {
            return redirect()
                ->route(config('cognito.routes.web.login_page', 'cognito.form.login'))
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'code', 'token', 'pass_code']))
                ->with('status', 'error')
                ->with('message', $systemErrorMsg)
                ->withErrors($errors);
        } else {
            $messageKey = $errorKey ? 'cognito::messages.error.' . $errorKey : null;
            $messageOutput = ($messageKey && Lang::has($messageKey)) ? trans($messageKey) : null;

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'code', 'token', 'pass_code']))
                ->with('status', 'error')
                ->with('message', $messageOutput)
                ->withErrors($errors);
        }
    } //Function ends

} //Class ends
