<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\NotAuthorizedException ;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use PHPUnit\Exception;

class AwsCognitoClient
{
    /**
     * Constant representing the user status as Confirmed.
     *
     * @var string
     */
    const USER_STATUS_CONFIRMED = 'CONFIRMED';


    /**
     * Constant representing the user needs a new password.
     *
     * @var string
     */
    const NEW_PASSWORD_CHALLENGE = 'NEW_PASSWORD_REQUIRED';


    /**
     * Constant representing the user needs to reset password.
     *
     * @var string
     */
    const RESET_REQUIRED_PASSWORD = 'RESET_REQUIRED';


    /**
     * Constant representing the force new password status.
     *
     * @var string
     */
    const FORCE_CHANGE_PASSWORD = 'FORCE_CHANGE_PASSWORD';


    /**
     * Constant representing the password reset required exception.
     *
     * @var string
     */
    const RESET_REQUIRED = 'PasswordResetRequiredException';


    /**
     * Constant representing the user not found exception.
     *
     * @var string
     */
    const USER_NOT_FOUND = 'UserNotFoundException';


    /**
     * Constant representing the username exists exception.
     *
     * @var string
     */
    const USERNAME_EXISTS = 'UsernameExistsException';


    /**
     * Constant representing the invalid password exception.
     *
     * @var string
     */
    const INVALID_PASSWORD = 'InvalidPasswordException';


    /**
     * Constant representing the code mismatch exception.
     *
     * @var string
     */
    const CODE_MISMATCH = 'CodeMismatchException';


    /**
     * Constant representing the expired code exception.
     *
     * @var string
     */
    const EXPIRED_CODE = 'ExpiredCodeException';


    /**
     * Constant representing the not authorized exception.
     *
     * @var string
     */
    const COGNITO_NOT_AUTHORIZED_ERROR = 'NotAuthorizedException';

    /**
     * Constant representing the SMS MFA challenge.
     *
     * @var string
     */
    const SMS_MFA = 'SMS_MFA';


    /**
     * @var CognitoIdentityProviderClient
     */
    protected $client;


    /**
     * @var string
     */
    protected $clientId;


    /**
     * @var string
     */
    protected $clientSecret;


    /**
     * @var string
     */
    protected $poolId;


    /**
     * @var bool
     */
    protected $boolClientSecret;


    /**
     * AwsCognitoClient constructor.
     * @param CognitoIdentityProviderClient $client
     * @param string $clientId
     * @param string $clientSecret
     * @param string $poolId
     * @param bool boolClientSecret
     */
    public function __construct(
        CognitoIdentityProviderClient $client,
        $clientId,
        $clientSecret,
        $poolId,
        $boolClientSecret
    )
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->poolId = $poolId;
        $this->boolClientSecret = $boolClientSecret;
    }

    /**
     * @return CognitoIdentityProviderClient
     */
    public function getCognitoIdentityProviderClient()
    {
        return $this->client;
    }

    /**
     * Checks if credentials of a user are valid.
     *
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html
     * @param string $username
     * @param string $password
     * @return \Aws\Result|bool
     */
    public function authenticate($username, $password)
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password
                ],
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['AuthParameters'] = array_merge($payload['AuthParameters'], [
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $response = $this->client->adminInitiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            throw $exception;
        }

        return $response;
    } //Function ends


    /**
     * Registers a user in the given user pool.
     *
     * @param $username
     * @param $password
     * @param array $attributes
     *
     * @return bool
     */
    public function register($username, $password, array $attributes = [])
    {

        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'Password' => $password,
                'UserAttributes' => $this->formatAttributes($attributes),
                'Username' => $username,
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload = array_merge($payload, [
                    'SecretHash' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $response = $this->client->signUp($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                return false;
            } //End if

            throw $e;
        } //Try-catch ends

        return (bool)$response['UserConfirmed'];
    } //Function ends


    /**
     * Send a password reset code to a user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ForgotPassword.html
     *
     * @param string $username
     * @param array $clientMetadata (optional)
     * @return string
     */
    public function sendResetLink($username, array $clientMetadata=null)
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'ClientMetadata' => $this->buildClientMetadata(['username' => $username], $clientMetadata),
                'Username' => $username,
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload = array_merge($payload, [
                    'SecretHash' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $result = $this->client->forgotPassword($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            } //End if

            throw $e;
        } //Try-catch ends

        return Password::RESET_LINK_SENT;
    } //Function ends


    /**
     * Reset a users password based on reset code.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ConfirmForgotPassword.html
     *
     * @param string $code
     * @param string $username
     * @param string $password
     * @return string
     */
    public function resetPassword($code, $username, $password)
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'ConfirmationCode' => $code,
                'Password' => $password,
                'Username' => $username,
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload = array_merge($payload, [
                    'SecretHash' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $this->client->confirmForgotPassword($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            } //End if

            if ($e->getAwsErrorCode() === self::INVALID_PASSWORD) {
                return Lang::has('passwords.password') ? 'passwords.password' : $e->getAwsErrorMessage();
            } //End if

            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return Password::INVALID_TOKEN;
            } //End if

            throw $e;
        } //Try-catch ends

        return Password::PASSWORD_RESET;
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
            $groups = $this->client->AdminListGroupsForUser([
                    'UserPoolId' => $this->poolId, // REQUIRED
                    'Username' => $username // REQUIRED
                ]
            );
            return $groups;
        } catch (CognitoIdentityProviderException $e) {
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
                    'GroupName' => $groupname, // REQUIRED
                    'UserPoolId' => $this->poolId, // REQUIRED
                    'Username' => $username // REQUIRED
                ]
            );
        } catch (CognitoIdentityProviderException $e) {
            throw $e;
        } //Try-catch ends

        return true;
    } //Function ends


    /**
     * Register a user and send them an email to set their password.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminCreateUser.html
     *
     * @param $username
     * @param $password (optional) (default=null)
     * @param array $attributes
     * @param array $clientMetadata (optional)
     * @param string $messageAction (optional)
     * @return bool $isUserEmailForcedVerified (false)
     */
    public function inviteUser(string $username, string $password=null, array $attributes = [],
                               array $clientMetadata=null, string $messageAction=null,
                               bool $isUserEmailForcedVerified = false, string $groupname=null)
    {
        //Force validate email
        if ($attributes['email'] && $isUserEmailForcedVerified) {
            $attributes['email_verified'] = 'true';
        } //End if

        //Generate payload
        $payload = [
            'UserPoolId' => $this->poolId,
            'Username' => $username,
            'UserAttributes' => $this->formatAttributes($attributes)
        ];

        //Set Client Metadata
        if (!empty($clientMetadata)) {
            $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
        } //End if

        //Set Temporary password
        if (!empty($password)) {
            $payload['TemporaryPassword'] = $password;
        } //End if

        //Set Message Action
        if (!empty($messageAction)) {
            $payload['MessageAction'] = $messageAction;
        } //End If

        if (config('cognito.add_user_delivery_mediums')!="DEFAULT") {
            $payload['DesiredDeliveryMediums'] = [
                config('cognito.add_user_delivery_mediums')
            ];
        } //End if

        try {
            $this->client->adminCreateUser($payload);

            //Add user to the group
            if (!empty($groupname)) {
                $this->adminAddUserToGroup($username, $groupname);
            } //End if
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                return false;
            } //End if

            throw $e;
        } //Try-catch ends

        return true;
    } //Function ends


    /**
     * Set a new password for a user that has been flagged as needing a password change.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminRespondToAuthChallenge.html.
     *
     * @param string $username
     * @param string $password
     * @param string $session
     * @return bool
     */
    public function confirmPassword($username, $password, $session)
    {
        try {
            //Generate payload
            $payload = [
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
                'Session' => $session,
                'ChallengeResponses' => [
                    'NEW_PASSWORD' => $password,
                    'USERNAME' => $username
                ],
                'ChallengeName' => 'NEW_PASSWORD_REQUIRED',
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['ChallengeResponses'] = array_merge($payload['ChallengeResponses'], [
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $this->client->AdminRespondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return Password::INVALID_TOKEN;
            } //End if

            throw $e;
        } //Try-catch ends

        return Password::PASSWORD_RESET;
    } //Function ends


    /**
     * @param string $username
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#admindeleteuser
     */
    public function deleteUser($username)
    {
        if (config('cognito.delete_user')) {
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
     * Changes the password for a specified user in a user pool.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ChangePassword.html
     *
     * @param string $accessToken
     * @param string $passwordOld
     * @param string $passwordNew
     * @return bool
     */
    public function changePassword(string $accessToken, string $passwordOld, string $passwordNew)
    {
        try {
            $this->client->changePassword([
                'AccessToken' => $accessToken,
                'PreviousPassword' => $passwordOld,
                'ProposedPassword' => $passwordNew
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
        return true;
    } //Function ends


    public function invalidatePassword($username)
    {
        $this->client->adminResetUserPassword([
            'UserPoolId' => $this->poolId,
            'Username' => $username,
        ]);
    } //Function ends


    public function confirmSignUp($username)
    {
        $this->client->adminConfirmSignUp([
            'UserPoolId' => $this->poolId,
            'Username' => $username,
        ]);
    } //Function ends


    public function confirmUserSignUp($username, $confirmationCode)
    {
        try {
            $this->client->confirmSignUp([
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
                'ConfirmationCode' => $confirmationCode,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return 'validation.invalid_user';
            } //End if

            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return 'validation.invalid_token';
            } //End if

            if ($e->getAwsErrorCode() === 'NotAuthorizedException' AND $e->getAwsErrorMessage() === 'User cannot be confirmed. Current status is CONFIRMED') {
                return 'validation.confirmed';
            } //End if

            if ($e->getAwsErrorCode() === 'LimitExceededException') {
                return 'validation.exceeded';
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    public function resendToken($username)
    {
        try {
            $this->client->resendConfirmationCode([
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ]);
        } catch (CognitoIdentityProviderException $e) {

            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return 'validation.invalid_user';
            } //End if

            if ($e->getAwsErrorCode() === 'LimitExceededException') {
                return 'validation.exceeded';
            } //End if

            if ($e->getAwsErrorCode() === 'InvalidParameterException') {
                return 'validation.confirmed';
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    // HELPER FUNCTIONS
    /**
     * Set a users attributes.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateUserAttributes.html.
     *
     * @param string $username
     * @param array $attributes
     * @return bool
     */
    public function setUserAttributes($username, array $attributes)
    {
        $this->client->AdminUpdateUserAttributes([
            'Username' => $username,
            'UserPoolId' => $this->poolId,
            'UserAttributes' => $this->formatAttributes($attributes),
        ]);

        return true;
    } //Function ends


    /**
     * Creates the Cognito secret hash.
     * @param string $username
     * @return string
     */
    protected function cognitoSecretHash($username)
    {
        return $this->hash($username . $this->clientId);
    } //Function ends


    /**
     * Creates a HMAC from a string.
     *
     * @param string $message
     * @return string
     */
    protected function hash($message)
    {
        $hash = hash_hmac(
            'sha256',
            $message,
            $this->clientSecret,
            true
        );

        return base64_encode($hash);
    } //Function ends


    /**
     * Get user details.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GetUser.html.
     *
     * @param string $username
     * @return mixed
     */
    public function getUser($username)
    {
        try {
            $user = $this->client->AdminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            return false;
        } //Try-catch ends

        return $user;
    } //Function ends


    /**
     * Responds to MFA challenge.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_RespondToAuthChallenge.html
     *
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     * @param string $challengeName
     * @return \Aws\Result|false
     */
    public function respondMFAChallenge(string $session, string $challengeValue, string $username, string $challengeName = AwsCognitoClient::SMS_MFA)
    {
        try {
            $challenge = $this->client->respondToAuthChallenge([
                'ClientId' => $this->clientId,
                'ChallengeName' => $challengeName,
                'ChallengeResponses' => [
                    'SMS_MFA_CODE' => $challengeValue,
                    'USERNAME' => $username,
                    'SECRET_HASH' => $this->cognitoSecretHash($username),
                ],
                'Session' => $session,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === 'NotAuthorizedException') {
                return 'mfa.not_authorized';
            } else if ($e->getAwsErrorCode() === self::CODE_MISMATCH) {
                return 'mfa.invalid_session';
            }

            return false;
        } //Try-catch ends

        return $challenge;
    } //Function ends


    /**
     * Get user details by access token.
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#getuser
     *
     * @param string $token
     * @return mixed
     */
    public function getUserByAccessToken(string $token)
    {
        try {
            $result = $this->client->getUser([
                'AccessToken' => $token
            ]);
        } catch (CognitoIdentityProviderException $e) {
            throw $e;
        } //Try-catch ends

        return $result;
    } //Function ends


    /**
     * Format attributes in Name/Value array.
     *
     * @param array $attributes
     * @return array
     */
    protected function formatAttributes(array $attributes)
    {
        $userAttributes = [];

        foreach ($attributes as $key => $value) {
            $userAttributes[] = [
                'Name' => $key,
                'Value' => $value,
            ];
        } //Loop ends

        return $userAttributes;
    } //Function ends


    /**
     * Build Client Metadata to be forwarded to Cognito.
     *
     * @param array $attributes
     * @return array $clientMetadata (optional)
     */
    protected function buildClientMetadata(array $attributes, array $clientMetadata=null)
    {
        if (!empty($clientMetadata)) {
            $userAttributes = array_merge($attributes, $clientMetadata);
        } else {
            $userAttributes = $attributes;
        } //End if

        return $userAttributes;
    } //Function ends

    
    /**
     * Generate a new token using refresh token.
     *
     * @see http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html
     * @param string $username
     * @param string $refreshToken
     * @return \Aws\Result|bool
     */
    public function refreshToken(string $username, string $refreshToken)
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => 'REFRESH_TOKEN_AUTH',
                'AuthParameters' => [
                    'REFRESH_TOKEN' => $refreshToken,
                ],
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['AuthParameters'] = array_merge($payload['AuthParameters'], [
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $response = $this->client->adminInitiateAuth($payload);

            // Reuse same refreshToken
            $response['AuthenticationResult']['RefreshToken'] = $refreshToken;
        } catch (CognitoIdentityProviderException $e) {
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends
    

    /**
     * Revoke all the access tokens from AWS Cognit for a specified refresh-token in a user pool.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#revoketoken
     *
     * @param string $refreshToken
     * @return bool
     */
    public function revokeToken(string $refreshToken)
    {
        try {
            $this->client->revokeToken([
                'ClientId'      => $this->clientId,
                'ClientSecret'  => $this->clientSecret,
                'Token'         => $refreshToken
            ]);
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends


    /**
     * Revoke the access-token from AWS Cognito in a user pool.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#globalsignout
     *
     * @param string $accessToken
     * @return bool
     */
    public function signOut(string $accessToken)
    {
        try {
            $this->client->globalSignOut([
                'AccessToken' => $accessToken
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

} //Class ends
