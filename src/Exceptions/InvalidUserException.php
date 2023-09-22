<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidUserException extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report($message="Invalid User Error", $code=null, Throwable $previous=null)
    {
        throw new HttpException(400, $message, $previous, [], $code);
    }


    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }
    
} //Class ends