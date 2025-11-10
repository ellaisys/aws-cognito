<?php

namespace Ellaisys\Cognito\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Throwable;
use PDOException;
use Psr\Log\LogLevel;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Services\JsonResponseService;
use Ellaisys\Cognito\Contracts\ExceptionContract;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Generic exception report
        $this->reportable(function (Throwable $e) {
            // You can log to an external service (Sentry, Bugsnag, etc.)
            Log::error($e->getMessage());
        });

        // Handle API exceptions gracefully
        $this->renderable(function (Throwable $e, $request) {
            return $this->handleException($e, $request);
        });
    } //Function ends


    protected function handleException(Throwable $e, $request)
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        $errorMessage = 'Something went wrong. Please try again later.';
        $errorKey = '';

        if ($e instanceof ValidationException) {
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY; //422
            $errorMessage = 'Data validation error';
        } elseif ($e instanceof ModelNotFoundException) {
            $statusCode = Response::HTTP_BAD_REQUEST; //400
            $errorMessage = 'Resource not found.';
        } elseif ($e instanceof NotFoundHttpException) {
            $statusCode = Response::HTTP_BAD_REQUEST; //400
            $errorMessage = $e->getMessage();
        } elseif ($e instanceof AwsCognitoException) {
            $statusCode = Response::HTTP_BAD_REQUEST; //400
            switch ($e->getMessage()) {
                case AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED:
                    $errorMessage = 'User authentication error';
                    $errorKey = AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED;
                    break;

                case AwsCognitoException::COGNITO_AUTH_USER_RESET_PASS:
                    $errorMessage = 'User password reset error';
                    $errorKey = AwsCognitoException::COGNITO_AUTH_USER_RESET_PASS;
                    break;

                case AwsCognitoException::COGNITO_AUTH_USERNAME_EXITS:
                    $errorMessage = 'User already exists';
                    $errorKey = AwsCognitoException::COGNITO_AUTH_USERNAME_EXITS;
                    break;
                
                default:
                    $errorMessage = $e->getMessage();
                    $errorKey = 'ERROR_COGNITO_DEFAULT';
                    break;
            } //End Switch
        } elseif (($e instanceof AuthenticationException) ||
            ($e instanceof InvalidUserException)) {
            $statusCode = Response::HTTP_UNAUTHORIZED; //401
            $errorMessage = 'Unauthenticated.';
            $errorKey = $e->getMessage();
        } elseif ($e instanceof AccessDeniedHttpException) {
            $statusCode = Response::HTTP_FORBIDDEN; //403
            $errorMessage = 'You do not have permission to perform this action.';
        } elseif (config('app.debug')) {
            // Show detailed info in debug mode
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => collect($e->getTrace())->take(3),
            ], 500);
        }

        if ($request->isJson() || $request->wantsJson() || $request->expectsJson()) {
            return $this->JsonResponseBuilder(
                $e, $request, $statusCode,
                $errorMessage, $errorKey
            );
        } else {
            return redirect()->back()
                ->withInput($request->input())
                ->withErrors($e->errors());
        }
    } //Function ends

    protected function JsonResponseBuilder(Throwable $e, $request,
        $statusCode,
        string $message='An error occurred',
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

} //Class ends
