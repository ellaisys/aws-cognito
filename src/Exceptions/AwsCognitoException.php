<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoException extends HttpException
{

    public function __construct(string $message = 'AWS Cognito Error', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        //previous is the exception thrown by AWS Cognito
        if ($previous instanceof CognitoIdentityProviderException) {
            $message = $previous->getAwsErrorMessage();
            $code = $previous->getStatusCode();
        } //End if

        parent::__construct(400, $message, $previous, $headers, $code);
    } //Function ends
    
} //Class ends