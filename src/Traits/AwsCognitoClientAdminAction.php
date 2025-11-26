<?php

namespace Ellaisys\Cognito\Traits;

use Config;
use Carbon\Carbon;

use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Illuminate\Support\Facades\Log;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\InvalidPasswordException;
use Aws\CognitoIdentityProvider\Exception\NotAuthorizedException ;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * WS Cognito Client for AWS Admin Users
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
    public function confirmSignUp($username)
    {
        $this->client->adminConfirmSignUp([
            'UserPoolId' => $this->poolId,
            'Username' => $username,
        ]);
    } //Function ends

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
     * Get user details.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminGetUser.html
     *
     * @param string $username
     * @return mixed
     */
    public function getUser($username)
    {
        try {
            return $this->client->adminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:getUser:CognitoIdentityProviderException');
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
     * @param string $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     *
     * @return \Aws\Result
     */
    protected function adminRespondToAuthChallenge(
        string $challengeName, string $session,
        string $challengeValue, string $username)
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
                'Session' => $session,
                'ChallengeName' => $challengeName,
            ];

            //Set challenge response
            $payload['ChallengeResponses'] = $this->buildChallengePayload(
                $challengeName, $challengeValue, $username
            );

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['ChallengeResponses'] = array_merge(
                    $payload['ChallengeResponses'], [
                        'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            //Execute the payload
            $response = $this->client->adminRespondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAdminAction:adminRespondToAuthChallenge:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    private function buildChallengePayload(string $challengeName,
        string $challengeValue, string $username): array
    {
        try {
            //Set challenge response
            $challengeResponse=['USERNAME' => $username];
            switch ($challengeName) {
                case CognitoChallengeTypes::SELECT_MFA_TYPE:
                    if (!in_array($challengeValue, ['SMS_MFA','EMAIL_MFA','SOFTWARE_TOKEN_MFA'], true)) {
                        throw new BadRequestHttpException('Invalid challenge value');
                    } //End if

                    $challengeResponse = array_merge($challengeResponse, [
                        'ANSWER' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SMS_MFA:
                    $challengeResponse = array_merge($challengeResponse, [
                        'SMS_MFA_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SMS_OTP:
                    $challengeResponse = array_merge($challengeResponse, [
                        'SMS_OTP_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::EMAIL_OTP:
                    $challengeResponse = array_merge($challengeResponse, [
                        'EMAIL_OTP_CODE' => $challengeValue
                    ]);
                    break;

                case CognitoChallengeTypes::SOFTWARE_TOKEN_MFA:
                    $challengeResponse = array_merge($challengeResponse, [
                        'SOFTWARE_TOKEN_MFA_CODE' => $challengeValue
                    ]);
                    break;
                
                case CognitoChallengeTypes::NEW_PASSWORD_CHALLENGE:
                    $challengeResponse = array_merge($challengeResponse, [
                        'NEW_PASSWORD' => $challengeValue
                    ]);
                    break;
                default:
                    # code...
                    break;
            } //End Switch
        } catch (Exception $e) {
            Log::error('AwsCognitoClientAdminAction:buildChallengePayload:Exception');
        } //Try-catch ends

        return $challengeResponse;
    } //Function ends

} //Trait ends
