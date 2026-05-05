<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Ellaisys\Cognito\Traits\AwsCognitoClientMFAAction;
use Ellaisys\Cognito\Traits\AwsCognitoClientAdminAction;
use Ellaisys\Cognito\Traits\AwsCognitoClientPasskeyAction;

use Exception;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\InvalidPasswordException;
use Aws\CognitoIdentityProvider\Exception\NotAuthorizedException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoClient
{
    use AwsCognitoClientMFAAction;
    use AwsCognitoClientAdminAction;
    use AwsCognitoClientPasskeyAction;

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
    public function authenticate(CognitoAuthFlowTypes $authFlow,
        string $username, ?string $password)
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => $authFlow->value,
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ];

            //Set Auth Parameters based on the Auth Flow
            switch ($authFlow) {
                case CognitoAuthFlowTypes::USER_SRP_AUTH:
                    $payload['AuthParameters'] = [
                        'USERNAME' => $username,
                        'SRP_A' => $password
                    ];
                    break;

                case CognitoAuthFlowTypes::CUSTOM_AUTH:
                    $payload['AuthParameters'] = [
                        'USERNAME' => $username
                    ];
                    break;

                case CognitoAuthFlowTypes::ADMIN_USER_PASSWORD_AUTH:
                case CognitoAuthFlowTypes::ADMIN_NO_SRP_AUTH:
                default:
                    $payload['AuthParameters'] = [
                        'USERNAME' => $username,
                        'PASSWORD' => $password
                    ];
                    break;
            } //End switch

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['AuthParameters'] = array_merge($payload['AuthParameters'], [
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $response = $this->client->adminInitiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClient:authenticate:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Registers a user in the given user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_SignUp.html
     *
     * @param string $username
     * @param string $password
     * @param array $attributes
     * @param array $clientMetadata (optional)
     * @return bool $groupname (optional)
     *
     * @return bool
     */
    public function register(string $username, string $password, array $attributes = [],
        ?array $clientMetadata = null, ?string $groupname = null)
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

            //Add user to the group
            if (!empty($groupname)) {
                $this->adminAddUserToGroup($username, $groupname);
            } //End if
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                throw new InvalidUserException(AwsCognitoException::COGNITO_AUTH_USERNAME_EXITS, $e);
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
    public function sendResetLink(string $username, ?array $clientMetadata = null)
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'ClientMetadata' => $this->buildClientMetadata([
                    'username' => $username
                ], $clientMetadata),
                'Username' => $username,
            ];

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload = array_merge($payload, [
                    'SecretHash' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $this->client->forgotPassword($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            } //End if

            throw $e;
        } //Try-catch ends

        return Password::RESET_LINK_SENT;
    } //Function ends

    /**
     * Allow users new password based on reset code, this is primarily part of
     * the reset password workflow.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ConfirmForgotPassword.html
     *
     * @param string $code
     * @param string $username
     * @param string $password
     * @return string
     */
    public function resetPassword(string $code, string $username, string $password)
    {
        try {
            //Initialize variables
            $returnValue = Password::PASSWORD_RESET;

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
                $returnValue = Password::INVALID_USER;
            } //End if

            if ($e->getAwsErrorCode() === self::INVALID_PASSWORD) {
                $returnValue = Lang::has('passwords.password') ? 'passwords.password' : $e->getAwsErrorMessage();
            } //End if

            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                $returnValue = Password::INVALID_TOKEN;
            } //End if

            throw $e;
        } //Try-catch ends

        return $returnValue;
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
    public function inviteUser(string $username, ?string $password = null, array $attributes = [],
        ?array $clientMetadata = null, ?string $messageAction = null,
        ?string $groupname = null)
    {
        //Validate phone for MFA
        if (config('cognito.mfa_setup')=="MFA_ENABLED" && empty($attributes['phone_number'])) {
            throw new HttpException(400, 'ERROR_MFA_ENABLED_PHONE_MISSING');
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
        if (config('cognito.add_user_delivery_mediums')!="NONE") {
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
                throw new InvalidUserException(AwsCognitoException::COGNITO_AUTH_USERNAME_EXITS, $e);
            } //End if

            throw AwsCognitoException::create($e);
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
            $this->adminRespondToAuthChallenge(
                CognitoChallengeTypes::NEW_PASSWORD_REQUIRED,
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

    /**
     * Confirm a user registration with a confirmation code for self registered
     * users.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ConfirmSignUp.html
     *
     * @param string $username
     * @param string $confirmationCode
     * @param array|null $clientMetadata
     * @return mixed
     * @throws Exception
     */
    public function confirmUserSignUp(string $username, string $confirmationCode,
        ?array $clientMetadata = null): mixed
    {
        try {
            //Initialize variables
            $returnValue = null;

            //Generate payload
            $payload = [
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
                'ConfirmationCode' => $confirmationCode,
                'ForceAliasCreation' => config('cognito.force_alias_creation', false),
            ];

            //Set Client Metadata
            if (!empty($clientMetadata)) {
                $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
            } //End if

            $returnValue = $this->client->confirmSignUp($payload);
        } catch (CognitoIdentityProviderException $e) {
            throw AwsCognitoException::create($e);
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Resend the confirmation code for a user that has not confirmed their email.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ResendConfirmationCode.html
     *
     * @param string $username
     * @param array|null $clientMetadata
     * @return mixed
     * @throws Exception
     */
    public function resendConfirmationCode(string $username, ?array $clientMetadata = null): mixed
    {
        try {
            //Initialize variables
            $returnValue = null;

            //Generate payload
            $payload = [
                'ClientId' => $this->clientId,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username
            ];

            //Set Client Metadata
            if (!empty($clientMetadata)) {
                $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
            } //End if

            //Execute the payload
            $returnValue = $this->client->resendConfirmationCode($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClient:resendConfirmationCode:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Set a users attributes.
     * http://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateUserAttributes.html.
     *
     * @param string $username
     * @param array $attributes
     * @return bool
     */
    public function setUserAttributes(string $username, array $attributes): bool
    {
        try {

            //Build payload
            $payload = [
                'Username' => $username,
                'UserPoolId' => $this->poolId,
                'UserAttributes' => $this->formatAttributes($attributes),
            ];

            //Execute the payload
            $this->client->AdminUpdateUserAttributes($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClient:setUserAttributes:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //End try

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
     * Get user details by access token.
     * https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#getuser
     *
     * @param string $accessToken
     * @return mixed
     */
    public function getUserByAccessToken(string $accessToken)
    {
        try {
            $result = $this->client->getUser([
                'AccessToken' => $accessToken
            ]);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClient:getUserByAccessToken:CognitoIdentityProviderException');
            throw AwsCognitoException::create($e);
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
    protected function buildClientMetadata(array $attributes, ?array $clientMetadata = null)
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
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html
     * @param string $username
     * @param string $refreshToken
     * @return \Aws\Result|bool
     */
    public function refreshToken(string $username, string $refreshToken)
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => CognitoAuthFlowTypes::REFRESH_TOKEN_AUTH->value,
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

            $response = $this->client->initiateAuth($payload);

            // Reuse same refreshToken
            $response['AuthenticationResult']['RefreshToken'] = $refreshToken;
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClient:refreshToken:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Revoke all the access tokens from AWS Cognito for a specified refresh-token in a user pool.
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
            Log::error('CognitoIdentityProvider:revokeToken:Exception');
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

            throw AwsCognitoException::create($e);
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends
        return true;
    } //Function ends

} //Class ends
