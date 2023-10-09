<?php

namespace Ellaisys\Cognito\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

use Throwable;
use Response;
use PDOException;
use Psr\Log\LogLevel;
use Illuminate\Http\Request;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
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
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Handle ValidationException
        $this->renderable(function (ValidationException $e, Request $request) {
            if ($request->isJson() || $request->wantsJson()) {
                return Response::json($e->errors(), 422);
            } else {
                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors($e->errors());
            } //End if
        });

        // Handle Exception
        $this->renderable(function (InvalidUserException $e, Request $request) {
            if ($request->isJson() || $request->wantsJson()) {
                return Response::json([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ], 400);
            } else {
                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors($e->getMessage());
            } //End if
        });

        // Handle Exception
        $this->renderable(function (AwsCognitoException $e, Request $request) {
            if ($request->isJson() || $request->wantsJson()) {
                return Response::json([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ], 400);
            } else {
                return redirect()->back()
                    ->withInput($request->input())
                    ->withErrors($e->getMessage());
            } //End if
        });

        $this->reportable(function (Throwable $e) {
            //
        });
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
    public function render($request, Throwable $e)
    {
        return parent::render($request, $e);
    } //Function ends

} //Class ends
