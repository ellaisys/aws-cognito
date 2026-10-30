<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Exceptions;

use Throwable;
use PDOException;

class DBConnectionException extends PDOException
{

    public function __construct(string $message = 'Database Connection Error',
        ?Throwable $previous = null, int $code = 400)
    {
        parent::__construct($message, (int) $code, $previous);
    }
    
} //Class ends
