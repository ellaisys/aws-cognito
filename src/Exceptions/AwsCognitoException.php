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
     * @param  Throwable  $previous
     * @param  array  $headers
     * @param  int  $code
     *
     * @return void
     */
    public function __construct(string $message="AWS Cognito Error",
        ?Throwable $previous=null, array $headers=[], int $code=400)
    {
        if ($previous instanceof CognitoIdentityProviderException && (!empty($previous->getAwsErrorCode()))) {
            $message = self::processAwsCognitoError($previous);
        } //End if

        parent::__construct(400, $message, $previous, $headers, $code);
    }

    /**
     * Static constructor / factory
     */
    public static function create(CognitoIdentityProviderException $e) {
        return new self(self::processAwsCognitoError($e), $e);
    }

    /**
     * Process AWS Cognito error and return proper error code
     *
     * @param  CognitoIdentityProviderException  $e
     *
     * @return string
     */
    private static function processAwsCognitoError(CognitoIdentityProviderException $e) : string {
        //Set proper route
        switch ($e->getAwsErrorCode()) {
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
                $errorCode = $e->getAwsErrorCode();
                break;
        } //End switch
        return $errorCode;
    } //Function ends
    
} //Class ends
