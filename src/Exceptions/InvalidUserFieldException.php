<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;

use Exception;
use Illuminate\Auth\AuthenticationException;

use Illuminate\Support\Facades\Log;

class InvalidUserFieldException extends Exception
{

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report($message = 'Invalid User Field Exception')
    {
        abort(403, $message);
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