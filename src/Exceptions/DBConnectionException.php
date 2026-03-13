<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;
use PDOException;

class DBConnectionException extends PDOException
{

    public function __construct(string $message = 'Database Connection Error',
        Throwable $previous = null, int $code = 400)
    {
        parent::__construct(401, $message, (int) $code, $previous);
    }
    
} //Class ends
