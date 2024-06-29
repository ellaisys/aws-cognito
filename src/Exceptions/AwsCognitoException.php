<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Symfony\Component\HttpKernel\Exception\HttpException;

class AwsCognitoException extends HttpException
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable  $previous
     * @param  array  $headers
     *
     * @return void
     */
    public function __construct($message="AWS Cognito Error", $code=null, Throwable $previous=null, array $headers=[])
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
    
} //Class ends
