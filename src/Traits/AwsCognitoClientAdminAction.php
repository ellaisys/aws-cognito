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
use Illuminate\Support\Facades\Password;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

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
     * @return AwsResult
     */
    final public function adminConfirmSignUp(string $username, ?array $clientMetadata = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ];

            //Set Client Metadata
            if (!empty($clientMetadata)) {
                $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
            } //End if

            return $this->client->adminConfirmSignUp($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminConfirmSignUp:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Enables the specified user as an administrator.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminenableuser
     *
     * @param string $username
     * @return AwsResult
     */
    final public function adminEnableUser(string $username): AwsResult
    {
        try {
            return $this->client->adminEnableUser([
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);

        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminEnableUser:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } // Function ends

    /**
     * Deactivates a user and revokes all access tokens for the user.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindisableuser
     *
     * @param string $username
     * @return AwsResult
     */
    final public function adminDisableUser(string $username): AwsResult
    {
        try {
            return $this->client->adminDisableUser([
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);

        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminDisableUser:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
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
    final public function adminUserGlobalSignOut(string $username): bool
    {
        try {
            $this->client->adminUserGlobalSignOut([
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminUserGlobalSignOut:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
        return true;
    } //Function ends

    /**
     * Resets the specified user's password in a user pool as an administrator.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#adminresetuserpassword
     *
     * @param string $username
     * @return \Aws\Result
     * @throws AwsCognitoException
     */
    final public function invalidatePassword(string $username): AwsResult
    {
        try {
            return $this->client->adminResetUserPassword([
                'UserPoolId' => $this->poolId,
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:invalidatePassword:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Get user details with username
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminGetUser.html
     *
     * @param string $username
     *
     * @return \Aws\Result
     * @throws AwsCognitoException
     */
    final public function adminGetUser(string $username): AwsResult
    {
        try {
            return $this->client->adminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminGetUser:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Deletes a user from a given user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminDeleteUser.html
     *
     * @param string $username
     *
     * @return \Aws\Result
     * @throws AwsCognitoException
     */
    final public function deleteUser(string $username): AwsResult
    {
        try {
            if (config('cognito.delete_user', false)) {
                return $this->client->adminDeleteUser([
                    'UserPoolId' => $this->poolId,
                    'Username' => $username,
                ]);
            } else {
                throw new AwsCognitoException(AwsCognitoException::COGNITO_CONFIG_INVALID);
            } //End if
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:deleteUser:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Sets the specified user's password in a user pool (as an administrator).
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminSetUserPassword.html
     *
     * @param string $username
     * @param string $password
     * @param bool $permanent (default: false) - Indicates whether the password is permanent or temporary.
     *
     * @return string
     */
    final public function adminSetUserPassword(string $username, string $password,
        bool $permanent = false): string
    {
        try {
            $this->client->adminSetUserPassword([
                'Password' => $password,
                'Permanent' => $permanent,
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminSetUserPassword:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return Password::PASSWORD_RESET;
    } //Function ends

    /**
     * Responds to an authentication challenge, as an administrator.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminRespondToAuthChallenge.html
     *
     * @param CognitoChallengeTypes $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     *
     * @return \Aws\Result
     */
    final public function adminRespondToAuthChallenge(
        CognitoChallengeTypes $challengeName, string $session,
        string $challengeValue, string $username): AwsResult
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
            return $this->client->adminRespondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminRespondToAuthChallenge:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } // Function ends

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
    final public function adminInitiateAuth(CognitoAuthFlowTypes $authFlow,
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

            return $this->client->adminInitiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAdminAction:adminInitiateAuth:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } // Function ends

} // Trait ends
