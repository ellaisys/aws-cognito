<?php

namespace Ellaisys\Cognito\Exceptions;

use Exception;
use Throwable;

use Illuminate\Support\Facades\Log;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoException extends HttpException
{
    const COGNITO_AUTH_USER_UNAUTHORIZED = 'ERROR_COGNITO_AUTH_USER_UNAUTHORIZED';
    const COGNITO_AUTH_USER_RESET_PASS = 'ERROR_COGNITO_AUTH_USER_RESET_PASSWORD';
    const COGNITO_AUTH_USERNAME_EXITS = 'ERROR_COGNITO_AUTH_USERNAME_EXITS';
    const COGNITO_USERNAME_INVALID = 'ERROR_COGNITO_USERNAME_INVALID';
    const COGNITO_USER_INVALID = 'ERROR_COGNITO_USER_INVALID';
    const COGNITO_RESET_PWD_REQ_INVALID = 'ERROR_COGNITO_RESET_PWD_REQ_INVALID';
    const COGNITO_RESET_PWD_FAILED = 'ERROR_COGNITO_RESET_PWD_FAILED';

    //cognito.validation.reset_required.invalid_user

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
                    $errorCode = self::COGNITO_AUTH_USER_RESET_PASS;
                    break;

                case 'NotAuthorizedException':
                    $errorCode = self::COGNITO_AUTH_USER_UNAUTHORIZED;
                    break;

                case 'UsernameExistsException':
                    $errorCode = self::COGNITO_AUTH_USERNAME_EXITS;
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
