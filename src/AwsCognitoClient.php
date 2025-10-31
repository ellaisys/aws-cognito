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

use Ellaisys\Cognito\Traits\AwsCognitoClientMFAAction;
use Ellaisys\Cognito\Traits\AwsCognitoClientAdminAction;

use Execption;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\InvalidPasswordException;
use Aws\CognitoIdentityProvider\Exception\NotAuthorizedException ;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoClient
{
    use AwsCognitoClientMFAAction;
    use AwsCognitoClientAdminAction;

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
     * Constant representing the SOFTWARE TOKEN MFA challenge.
     *
     * @var string
     */
    const SOFTWARE_TOKEN_MFA = 'SOFTWARE_TOKEN_MFA';


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
        } //Try-catch ends

        return $response;
    } //Function ends


    /**
     * Registers a user in the given user pool.
     *
     * @param $username
     * @param $password
     * @param array $attributes
     * @param array $clientMetadata (optional)
     * @return bool $groupname (optional)
     *
     * @return bool
     */
    public function register($username, $password, array $attributes = [],
        array $clientMetadata=null, string $groupname=null)
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

            //Set Client Metadata
            if (!empty($clientMetadata)) {
                $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
            } //End if

            $response = $this->client->signUp($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                throw new InvalidUserException('ERROR_COGNITO_USER_EXISTS', $e);
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
                'GroupName' => $groupname,
                'UserPoolId' => $this->poolId,
                'Username' => $username
            ]);
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
     * @return bool $groupname (optional)
     */
    public function inviteUser(string $username, string $password=null, array $attributes = [],
        array $clientMetadata=null, string $messageAction=null,
        string $groupname=null)
    {
        //Validate phone for MFA
        if (config('cognito.mfa_setup')=="MFA_ENABLED") {
            if (empty($attributes['phone_number'])) { throw new HttpException(400, 'ERROR_MFA_ENABLED_PHONE_MISSING'); }
        } //End if        
        
        //Force validate email
        if ($attributes['email']) {
            $attributes['email_verified'] = config('cognito.force_new_user_email_verified', false)? 'true' : 'false';
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
        if (!empty($messageAction) && in_array($messageAction, ['RESEND', 'SUPPRESS'])) {
            $payload['MessageAction'] = $messageAction;
        } //End If

        //Set Delivery Mediums
        if ((config('cognito.add_user_delivery_mediums')!="NONE")) {
            if (config('cognito.add_user_delivery_mediums')=="BOTH") {
                $payload['DesiredDeliveryMediums'] = ['EMAIL', 'SMS'];
            } else {
                $defaultDeliveryMedium = config('cognito.add_user_delivery_mediums', "EMAIL");
                $payload['DesiredDeliveryMediums'] = [ $defaultDeliveryMedium ];
            } //End if
        } //End if
        if (config('cognito.mfa_setup')=="MFA_ENABLED") {
            $defaultDeliveryMedium = 'SMS';
            $payload['DesiredDeliveryMediums'] = [ $defaultDeliveryMedium ];
        } //End if

        try {
            $response = $this->client->adminCreateUser($payload);

            //Add user to the group
            if (!empty($groupname)) {
                $this->adminAddUserToGroup($username, $groupname);
            } //End if
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                throw new InvalidUserException('ERROR_COGNITO_USER_EXISTS', $e);
            } //End if

            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends


    /**
     * Set a new password for a user that has been flagged as needing a password change.
     *
     * @param string $username
     * @param string $password
     * @param string $session
     * 
     * @return bool
     */
    public function confirmPassword($username, $password, $session)
    {
        try {
            $response = $this->adminRespondToAuthChallenge(
                AwsCognitoClient::NEW_PASSWORD_CHALLENGE,
                $session, $password, $username
            );
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
            $challengeResponse=['USERNAME' => $username];
            switch ($challengeName) {
                case 'SMS_MFA':
                    $challengeResponse = array_merge($challengeResponse, [
                        'SMS_MFA_CODE' => $challengeValue
                    ]);
                    break;

                case 'SOFTWARE_TOKEN_MFA':
                    $challengeResponse = array_merge($challengeResponse, [
                        'SOFTWARE_TOKEN_MFA_CODE' => $challengeValue
                    ]);
                    break;
                
                case 'NEW_PASSWORD_REQUIRED':
                    $challengeResponse = array_merge($challengeResponse, [
                        'NEW_PASSWORD' => $challengeValue
                    ]);
                    break;
                default:
                    # code...
                    break;
            } //End Switch
            $payload['ChallengeResponses'] = $challengeResponse;

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['ChallengeResponses'] = array_merge($payload['ChallengeResponses'], [
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            //Execute the payload
            $response = $this->client->adminRespondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $e) {
            throw $e;
        } //Try-catch ends

        return $response;
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
            return $this->client->adminGetUser([
                'Username' => $username,
                'UserPoolId' => $this->poolId,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            return false;
        } //Try-catch ends
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
