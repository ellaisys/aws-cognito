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

use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoException extends HttpException
{
    const COGNITO_AUTH_USER_UNAUTHORIZED = 'ERROR_COGNITO_AUTH_USER_UNAUTHORIZED';
    const COGNITO_AUTH_USER_RESET_PASS = 'ERROR_COGNITO_AUTH_USER_RESET_PASSWORD';
    const COGNITO_AUTH_USERNAME_EXISTS = 'ERROR_COGNITO_AUTH_USERNAME_EXISTS';
    const COGNITO_AUTH_CODE_INVALID = 'ERROR_COGNITO_AUTH_CODE_INVALID';
    const COGNITO_USERNAME_INVALID = 'ERROR_COGNITO_USERNAME_INVALID';
    const COGNITO_USER_INVALID = 'ERROR_COGNITO_USER_INVALID';
    const COGNITO_RESET_PWD_REQ_INVALID = 'ERROR_COGNITO_RESET_PWD_REQ_INVALID';
    const COGNITO_RESET_PWD_FAILED = 'ERROR_COGNITO_RESET_PWD_FAILED';
    const COGNITO_AUTH_POOL_CONFIG_INVALID = 'ERROR_COGNITO_AUTH_POOL_CONFIG_INVALID';
    const COGNITO_THROTTLING_LIMIT = 'ERROR_COGNITO_THROTTLING_LIMIT';
    const COGNITO_WEB_AUTH_INVALID = 'ERROR_COGNITO_WEB_AUTH_INVALID';
    const COGNITO_INVALID_PASSWORD = 'ERROR_COGNITO_INVALID_PASSWORD';
    const COGNITO_MFA = 'ERROR_COGNITO_MFA';

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
        ?Throwable $previous=null, array $headers=[], int $statusCode=400, int $code=0)
    {
        if ($previous instanceof CognitoIdentityProviderException && (!empty($previous->getAwsErrorCode()))) {
            [$message, $statusCode, $headers, $code] = self::processAwsCognitoError($previous);
        } //End if

        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * Static constructor / factory
     */
    public static function create(CognitoIdentityProviderException $exception): self {
        [$message, $statusCode, $headers, $code] = self::processAwsCognitoError($exception);
        return new self($message, $exception, $headers, $statusCode, $code);
    }

    /**
     * Process AWS Cognito error and return proper error code
     *
     * @param  CognitoIdentityProviderException  $exception
     *
     * @return array [string $message, int $statusCode, array $headers, int $code]
     */
    private static function processAwsCognitoError(CognitoIdentityProviderException $exception): array
    {
        //Set proper route
        switch ($exception->getAwsErrorCode()) {
            case 'PasswordResetRequiredException':
                $errorCode = self::COGNITO_AUTH_USER_RESET_PASS;
                break;

            case 'NotAuthorizedException':
                $errorCode = self::COGNITO_AUTH_USER_UNAUTHORIZED;
                break;

            case 'UserNotFoundException':
                $errorCode = self::COGNITO_USER_INVALID;
                break;

            case 'UsernameExistsException':
                $errorCode = self::COGNITO_AUTH_USERNAME_EXISTS;
                break;

            case 'EnableSoftwareTokenMFAException':
            case 'SoftwareTokenMFANotFoundException':
                $errorCode = self::COGNITO_MFA;
                break;

            case 'CodeMismatchException':
            case 'ExpiredCodeException':
                $errorCode = self::COGNITO_AUTH_CODE_INVALID;
                break;

            case 'LimitExceededException':
            case 'TooManyRequestsException':
                $errorCode = self::COGNITO_THROTTLING_LIMIT;
                break;

            case 'WebAuthnNotEnabledException':
            case 'WebAuthnChallengeNotFoundException':
            case 'WebAuthnRelyingPartyMismatchException':
            case 'WebAuthnClientMismatchException':
            case 'WebAuthnOriginNotAllowedException':
                $errorCode = self::COGNITO_WEB_AUTH_INVALID;
                break;

            case 'InvalidUserPoolConfigurationException':
                $errorCode = self::COGNITO_AUTH_POOL_CONFIG_INVALID;
                break;

            case 'InvalidPasswordException':
                $errorCode = self::COGNITO_INVALID_PASSWORD;
                break;
            
            case 'ResourceNotFoundException':
            case 'InvalidParameterException':
            case 'InternalErrorException':
            default:
                $errorCode = $exception->getAwsErrorCode();
                break;
        } //End switch
        
        return [
                $errorCode ?? 'AWS Cognito Error',
                $exception->getStatusCode() ?? 400,
                [],
                0
            ];
    } //Function ends
    
} //Class ends
