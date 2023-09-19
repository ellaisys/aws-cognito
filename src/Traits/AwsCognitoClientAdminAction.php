<?php

namespace Ellaisys\Cognito\Traits;

use Config;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * WS Cognito Client for AWS Admin Users
 */
trait AwsCognitoClientAdminAction
{

    /**
     * Enables the specified user as an administrator.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminenableuser
     *
     * @param string $username
     * @return bool
     */
    public function adminEnableUser(string $username)
    {
        try {
            return $this->client->adminEnableUser([
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);

        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::COGNITO_NOT_AUTHORIZED_ERROR) {
                return true;
            } //End if

            throw $e;
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends


    /**
     * Deactivates a user and revokes all access tokens for the user.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindisableuser
     *
     * @param string $username
     * @return bool
     */
    public function adminDisableUser(string $username)
    {
        try {
            return $this->client->adminDisableUser([
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);

        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::COGNITO_NOT_AUTHORIZED_ERROR) {
                return true;
            } //End if

            throw $e;
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends


    /**
     * Signs out a user from all devices. It also invalidates all refresh tokens that Amazon Cognito has 
     * issued to a user. The user's current access and ID tokens remain valid until they expire.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminuserglobalsignout
     *
     * @param string $username
     * @return bool
     */
    public function adminUserGlobalSignOut(string $username)
    {
        try {
            return $this->client->adminUserGlobalSignOut([
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);

        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::COGNITO_NOT_AUTHORIZED_ERROR) {
                return true;
            } //End if

            throw $e;
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends


    /**
     * Resets the specified user's password in a user pool as an administrator.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminresetuserpassword
     *
     * @param string $username
     * @return bool
     */
    public function invalidatePassword(string $username)
    {
        try {
            return $this->client->adminResetUserPassword([
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ]);

        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::COGNITO_NOT_AUTHORIZED_ERROR) {
                return true;
            } //End if

            throw $e;
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends


    /**
     * Gets configuration information and metadata of the specified user pool.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#describeuserpool
     *
     * @return mixed
     */
    public function describeUserPool()
    {
        try {
            return $this->client->describeUserPool([
                'UserPoolId' => $this->poolId
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::COGNITO_NOT_AUTHORIZED_ERROR) {
                return true;
            } //End if

            throw $e;
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends

} //Trait ends