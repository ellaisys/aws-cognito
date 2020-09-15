<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvalidUserModelException extends Exception
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        throw new ModelNotFoundException();
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