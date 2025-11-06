<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

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
    public function __construct(string $message="AWS Cognito Error",
        int $code=0, Throwable $previous=null, array $headers=[])
    {
        if ($previous instanceof CognitoIdentityProviderException && (!empty($previous->getAwsErrorCode()))) {
            //Set proper route
            switch ($previous->getAwsErrorCode()) {
                case 'PasswordResetRequiredException':
                    $errorCode = 'cognito.validation.auth.reset_password';
                    break;

                case 'NotAuthorizedException':
                    $errorCode = 'cognito.validation.auth.user_unauthorized';
                    break;
                
                default:
                    $errorCode = $previous->getAwsErrorCode();
                    break;
            } //End switch
            $message = $errorCode;
        } //End if

        parent::__construct(400, $message, $previous, $headers, $code);
    }
    
} //Class ends
