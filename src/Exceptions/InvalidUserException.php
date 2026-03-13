<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidUserException extends HttpException
{

    public function __construct(string $message = 'Invalid Cognito User',
        ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
    
} //Class ends
