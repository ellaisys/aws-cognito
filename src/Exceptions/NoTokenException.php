<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpKernel\Exception\HttpException;

class NoTokenException extends HttpException
{

    public function __construct(string $message = 'Authentication token not provided',
        Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(401, $message, $previous, $headers, $code);
    }
    
} //Class ends
