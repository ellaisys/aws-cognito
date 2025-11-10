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
    
} //Class ends
