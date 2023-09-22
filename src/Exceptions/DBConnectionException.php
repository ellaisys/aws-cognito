<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use PDOException;

class DBConnectionException extends PDOException
{
    /**
     * Report the exception.
     *
     * @return void
     */
    public function report($message="Database Connection Error", $code=null, Throwable $previous=null)
    {
        parent::report($message, [], $code, $previous);
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