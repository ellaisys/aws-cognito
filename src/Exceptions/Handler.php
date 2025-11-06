<?php

namespace Ellaisys\Cognito\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Throwable;
use Response;
use PDOException;
use Psr\Log\LogLevel;
use Illuminate\Http\Request;
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
     * @var \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected $parentHandler;


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
        JsonResponseService $response, array $format)
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
        //$this->handleExceptions();
    } //Function ends

    
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render(Request $request, Throwable $e)
    {
        Log::error('Exception occurred', [
            'request' => $request->all(),
            'exception' => $e
        ]);

        $this->handleExceptions($e, $request);

        return parent::render($request, $e);
    } //Function ends


    public function shouldReport(Throwable $e): bool
    {
        // Avoid reporting certain exceptions
        if (in_array(get_class($e), $this->dontReport, true)) {
            return false;
        }

        return parent::shouldReport($e);
    }


    public function report(Throwable $e): void
    {
        Log::error('Reporting Exception', [
            'exception' => $e
        ]);
        if ($this->shouldReport($e)) {
            parent::report($e);
        }
    }


    protected function handleExceptions(Throwable $e, Request $request)
    {
        Log::error('Handling Exception', [
            'exception' => $e
        ]);

        // Handle ValidationException
        $this->reportable(function (ValidationException $e, Request $request) {
            if ($request->isJson() || $request->wantsJson()) {
                return $this->JsonResponseBuilder($e, $request, 422);
            } else {
                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors($e->errors());
            } //End if
        });

        // Handle Exception
        $this->reportable(function (InvalidUserException | AuthenticationException $e, Request $request) {
            if ($request->isJson() || $request->wantsJson()) {
                return $this->JsonResponseBuilder($e, $request, 401, 'Unauthenticated');
            } else {
                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors($e->getMessage());
            } //End if
        });

        // Handle Exception
        $this->reportable(function (AwsCognitoException $e, Request $request) {
            if ($request->isJson() || $request->wantsJson()) {
                return $this->JsonResponseBuilder($e, $request, 400);
            } else {
                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors($e->getMessage());
            } //End if
        });

        $this->reportable(function (Throwable $e, Request $request) {
            if ($e instanceof AuthenticationException) {
                Log::error('AWS Cognito Exception', [
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return $this->JsonResponseBuilder($e, $request, 400, 'Unauthenticated');
            } //End if
        });

        parent::handleExceptions($e);
    }

    protected function JsonResponseBuilder(Throwable $e, Request $request, int $statusCode, string $message='An error occurred'): array
    {
        return $this->response->fail(
            $e,
            $statusCode,
            $message
        );
    }

} //Class ends
