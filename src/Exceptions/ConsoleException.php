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

use Exception;
use Throwable;

use Illuminate\Support\Facades\Log;

use RuntimeException;

class ConsoleException extends RuntimeException
{

    public function __construct(string $message = 'Console command failed',
        ?Throwable $previous = null, int $code = 0)
    {
        parent::__construct($message, $code, $previous);
    }
    
} //Class ends
