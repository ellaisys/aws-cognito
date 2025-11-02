<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;

use Exception;
use Illuminate\Auth\AuthenticationException;

class InvalidTokenException extends Exception
{

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report($message = 'Invalid Authentication Token')
    {
        throw new AuthenticationException($message);
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
