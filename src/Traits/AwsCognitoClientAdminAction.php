<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Traits;

use Config;
use Carbon\Carbon;

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Ellaisys\Cognito\Enums\CognitoDeviceRememberedStatus;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client for AWS Admin Users
 */
trait AwsCognitoClientAdminAction
{
    /**
     * Confirms user sign-up as an administrator.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminConfirmSignUp.html
     *
     * @param string $username
     * @return bool
     */
    public function adminConfirmSignUp($username): bool
    {
        try {
            $this->client->adminConfirmSignUp([
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        } //Try-catch ends

    } //Function ends

    /**
     * Enables the specified user as an administrator.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminenableuser
     *
     * @param string $username
     * @return bool
     */
    public function adminEnableUser(string $username): bool
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
    public function adminDisableUser(string $username): bool
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
    public function adminUserGlobalSignOut(string $username): bool
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
    public function invalidatePassword(string $username): bool
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
    public function describeUserPool(): mixed
    {
        try {
            return $this->client->describeUserPool([
                'UserPoolId' => $this->poolId
            ]);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:describeUserPool:CognitoIdentityProviderException');
            if ($e->getAwsErrorCode() === self::COGNITO_NOT_AUTHORIZED_ERROR) {
                return true;
            } //End if

            throw $e;
        } catch (Exception $e) {
            Log::error('AwsCognitoClientAdminAction:describeUserPool:Exception');
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends

    /**
     * Get user details with username
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminGetUser.html
     *
     * @param string $username
     *
     * @return mixed
     */
    public function adminGetUser(string $username): mixed
    {
        try {
            return $this->client->adminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:adminGetUser:CognitoIdentityProviderException');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Gets the user's groups from Cognito
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminListGroupsForUser.html
     *
     * @param string $username
     * @return \Aws\Result
     */
    public function adminListGroupsForUser(string $username)
    {
        try {
            return $this->client->AdminListGroupsForUser([
                    'UserPoolId' => $this->poolId, // REQUIRED
                    'Username' => $username // REQUIRED
                ]
            );
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:adminListGroupsForUser:CognitoIdentityProviderException');
            throw $e;
        } //Try-catch ends

        return false;
    } //Function ends

    /**
     * Add a user to a given group
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminAddUserToGroup.html
     *
     * @param string $username
     * @param string $groupname
     *
     * @return bool
     */
    public function adminAddUserToGroup(string $username, string $groupname)
    {
        try {
            $this->client->adminAddUserToGroup([
                'GroupName' => $groupname,
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:adminAddUserToGroup:CognitoIdentityProviderException');
            throw $e;
        } //Try-catch ends

        return true;
    } //Function ends

    /**
     * @param string $username
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindeleteuser
     */
    public function deleteUser($username)
    {
        if (config('cognito.delete_user', false)) {
            $this->client->adminDeleteUser([
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ]);
        } //End if
    } //Function ends

    /**
     * Sets the specified user's password in a user pool as an administrator.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminSetUserPassword.html
     *
     * @param string $username
     * @param string $password
     * @param bool $permanent
     * @return bool
     */
    public function setUserPassword($username, $password, $permanent = true)
    {
        try {
            $this->client->adminSetUserPassword([
                'Password' => $password,
                'Permanent' => $permanent,
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            } //End if

            if ($e->getAwsErrorCode() === self::INVALID_PASSWORD) {
                return Lang::has('passwords.password') ? 'passwords.password' : $e->getAwsErrorMessage();
            } //End if

            throw $e;
        } //Try-catch ends

        return Password::PASSWORD_RESET;
    } //Function ends

    /**
     * Responds to an authentication challenge, as an administrator.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminRespondToAuthChallenge.html
     *
     * @param CognitoChallengeTypes $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     *
     * @return \Aws\Result
     */
    public function adminRespondToAuthChallenge(
        CognitoChallengeTypes $challengeName, string $session,
        string $challengeValue, string $username)
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
                'Session' => $session,
                'ChallengeName' => $challengeName->value,
            ];

            //Set challenge response
            $payload['ChallengeResponses'] = $this->buildChallengePayload(
                $challengeName, $challengeValue, $username
            );

            //Add Secret Hash in case of Client Secret being configured
            $payload = $this->cognitoSecretHash($username, $payload);

            //Execute the payload
            $response = $this->client->adminRespondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminRespondToAuthChallenge:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Updates the device status as an administrator.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateDeviceStatus.html
     *
     * @param string $username
     * @param string $deviceKey
     * @param CognitoDeviceRememberedStatus $rememberStatus (default: REMEMBERED)
     *
     * @return AwsResult
     */
    public function adminUpdateDeviceStatus(string $username, string $deviceKey,
        CognitoDeviceRememberedStatus $rememberStatus=CognitoDeviceRememberedStatus::REMEMBERED): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'DeviceKey' => $deviceKey,
                'UserPoolId' => $this->poolId,
                'DeviceRememberedStatus' => $rememberStatus->value,
            ];

            $response = $this->client->adminUpdateDeviceStatus($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminUpdateDeviceStatus:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Get the device details as an administrator.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminGetDevice.html
     *
     * @param string $username
     * @param string $deviceKey
     *
     * @return AwsResult
     */
    public function adminGetDevice(string $username, string $deviceKey): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'DeviceKey' => $deviceKey,
                'UserPoolId' => $this->poolId,
            ];

            //Execute the payload
            $response = $this->client->adminGetDevice($payload);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:adminGetDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($e);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Forget/Delete the device as an administrator.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminForgetDevice.html
     *
     * @param string $username
     * @param string $deviceKey
     *
     * @return AwsResult
     */
    public function adminForgetDevice(string $username, string $deviceKey): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'DeviceKey' => $deviceKey,
                'UserPoolId' => $this->poolId,
            ];

            //Execute the payload
            $response = $this->client->adminForgetDevice($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminForgetDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Declares an authentication flow and initiates sign-in for a user
     * in the Amazon Cognito user directory as an administrator.
     *
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html
     * @param CognitoAuthFlowTypes $authFlow
     * @param array $payloadData
     * @param string $username
     * @return AwsResult
     */
    public function adminInitiateAuth(CognitoAuthFlowTypes $authFlow,
        array $payloadData, string $username): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => $authFlow->value,
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ];

            //Add other payload data
            $payload = array_merge($payload, $payloadData);

            //Add Secret Hash in case of Client Secret being configured
            $payload = $this->cognitoSecretHash($username, $payload);

            $response = $this->client->adminInitiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminInitiateAuth:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
