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
     * AwsCognitoClient constructor.
     * @param CognitoIdentityProviderClient $client
     * @param string $clientId
     * @param string $clientSecret
     * @param string $poolId
     */
    public function __construct(
        CognitoIdentityProviderClient $client,
        $clientId,
        $clientSecret,
        $poolId
    )
    {
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->poolId = $poolId;
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
            $response = $this->client->adminInitiateAuth([
                'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
                'AuthParameters' => [
                    'USERNAME' => $username,
                    'PASSWORD' => $password,
                    'SECRET_HASH' => $this->cognitoSecretHash($username),
                ],
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ]);
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
            $response = $this->client->signUp([
                'ClientId' => $this->clientId,
                'Password' => $password,
                'SecretHash' => $this->cognitoSecretHash($username),
                'UserAttributes' => $this->formatAttributes($attributes),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                return false;
            } //End if

            throw $e;
        }

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
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ];

            $result = $this->client->forgotPassword($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            } //End if

            throw $e;
        }

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


            $this->client->confirmForgotPassword([
                'ClientId' => $this->clientId,
                'ConfirmationCode' => $code,
                'Password' => $password,
                'SecretHash' => $this->cognitoSecretHash($username),
                'Username' => $username,
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USER_NOT_FOUND) {
                return Password::INVALID_USER;
            }

            if ($e->getAwsErrorCode() === self::INVALID_PASSWORD) {
                return Lang::has('passwords.password') ? 'passwords.password' : $e->getAwsErrorMessage();
            }

            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return Password::INVALID_TOKEN;
            }

            throw $e;
        }

        return Password::PASSWORD_RESET;
    } //Function ends


    /**
     * Register a user and send them an email to set their password.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminCreateUser.html
     *
     * @param $username
     * @param array $attributes
     * @param array $clientMetadata (optional)
     * @return bool
     */
    public function inviteUser(string $username, string $password=null, array $attributes = [], array $clientMetadata=null)
    {
        //Force validate email
        if ($attributes['email']) {
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

        if (config('cognito.add_user_delivery_mediums')!="DEFAULT") {
            $payload['DesiredDeliveryMediums'] = [
                config('cognito.add_user_delivery_mediums')
            ];
        } //End if

        try {
            $this->client->adminCreateUser($payload);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::USERNAME_EXISTS) {
                return false;
            } //End if

            throw $e;
        }

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
            $this->client->AdminRespondToAuthChallenge([
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
                'Session' => $session,
                'ChallengeResponses' => [
                    'NEW_PASSWORD' => $password,
                    'USERNAME' => $username,
                    'SECRET_HASH' => $this->cognitoSecretHash($username),
                ],
                'ChallengeName' => 'NEW_PASSWORD_REQUIRED',
            ]);
        } catch (CognitoIdentityProviderException $e) {
            if ($e->getAwsErrorCode() === self::CODE_MISMATCH || $e->getAwsErrorCode() === self::EXPIRED_CODE) {
                return Password::INVALID_TOKEN;
            } //End if

            throw $e;
        }

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
        }

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
        }
        return true;
    }


    public function invalidatePassword($username)
    {
        $this->client->adminResetUserPassword([
            'UserPoolId' => $this->poolId,
            'Username' => $username,
        ]);
    }


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
        }
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
        }
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
        }

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
        }

        return $challenge;
    }

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

} //Class ends
