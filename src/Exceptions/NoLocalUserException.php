<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NoLocalUserException extends HttpException
{
    public function __construct(string $message = 'User does not exist locally.', Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(401, $message, $previous, $headers, $code);
    }
} //Class ends
