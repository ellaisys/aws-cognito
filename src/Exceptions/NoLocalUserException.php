<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NoLocalUserException extends HttpException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @param  \Throwable  $previous
     * @param  int  $code
     * @param  array  $headers
     *
     * @return void
     */
    public function __construct(string $message = 'User does not exist locally.', ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(401, $message, $previous, $headers, $code);
    }
} //Class ends
