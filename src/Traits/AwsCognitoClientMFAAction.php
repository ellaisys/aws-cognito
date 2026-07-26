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

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client for MFA Actions
 */
trait AwsCognitoClientMFAAction
{
    /**
     * Generate the Software MFA Token for the user
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AssociateSoftwareToken.html
     *
     * @param string $accessToken (optional)
     * @param string $session (optional)
     *
     * @return mixed
     */
    public function associateSoftwareTokenMFA(?string $accessToken = null, ?string $session = null)
    {
        try {
            //Build payload
            $payload = [];

            //Access Token based Software MFA Token
            if (!empty($accessToken)) {
                $payload = array_merge($payload, [ 'AccessToken' => $accessToken ]);
                $session=null;
            } //End if

            //Session based Software MFA Token
            if (!empty($session)) {
                $payload = array_merge($payload, [ 'Session' => $session ]);
            } //End if

            $response = $this->client->associateSoftwareToken($payload);
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:associateSoftwareTokenMFA:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Verify the user code for the Software MFA Token
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_VerifySoftwareToken.html
     *
     * @param string $userCode
     * @param string $accessToken (optional)
     * @param string $session (optional)
     * @param string $deviceName (optional)
     *
     * @return mixed
     */
    public function verifySoftwareTokenMFA(string $userCode, ?string $accessToken = null,
        ?string $session = null, ?string $deviceName = null)
    {
        try {
            //Build payload
            $payload = [
                'UserCode' => $userCode,
                'FriendlyDeviceName' => $deviceName
            ];

            //Access Token based Software MFA Token
            if (!empty($accessToken)) {
                $payload = array_merge($payload, [ 'AccessToken' => $accessToken ]);
                $session=null;
            } //End if

            //Session based Software MFA Token
            if (!empty($session)) {
                $payload = array_merge($payload, [ 'Session' => $session ]);
            } //End if

            $response = $this->client->verifySoftwareToken($payload);
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:verifySoftwareTokenMFA:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Set user MFA preference setting by self/user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_SetUserMFAPreference.html
     *
     * @param string $accessToken
     * @param bool $isEnable (optional)
     *
     * @return \Aws\Result
     * @throws \Ellaisys\Cognito\Exceptions\AwsCognitoException
     */
    public function setUserMFAPreference(string $accessToken, bool $isEnable=false):AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'UserPoolId' => $this->poolId
            ];
            $payload = array_merge($payload, $this->buildPreferencePayload($isEnable));

            $response = $this->client->setUserMFAPreference($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientMFAAction:setUserMFAPreference:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Set user MFA preference setting by admin.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminSetUserMFAPreference.html
     *
     * @param string $username
     * @param bool $isEnable (optional)
     *
     * @return \Aws\Result
     * @throws \Ellaisys\Cognito\Exceptions\AwsCognitoException
     */
    public function adminSetUserMFAPreference(string $username, bool $isEnable=false): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'UserPoolId' => $this->poolId
            ];
            $payload = array_merge($payload, $this->buildPreferencePayload($isEnable));

            $response = $this->client->adminSetUserMFAPreference($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientMFAAction:adminSetUserMFAPreference:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Private method for Building the MFA preference payload
     *
     * @param bool $isEnable
     * @return array
     */
    private function buildPreferencePayload(bool $isEnable): array
    {
        //Initialize variables
        $returnValue = [];

        try {
            //Get the MFA type configuration
            $isMfaEnabled = (config('cognito.mfa_setup') != 'OFF')?true:false;
            $listMfaTypes = explode(',', config('cognito.mfa_type', 'SOFTWARE_TOKEN_MFA'));

            if (empty($listMfaTypes) || !is_array($listMfaTypes)) {
                throw new BadRequestHttpException('No MFA type is configured in the system. Please configure at least one MFA type in the config/cognito.php file.');
            } //End if

            //Set the preferred MFA type as the first type in the list
            $preferredMfaType = $listMfaTypes[0];

            // Build MFA preference objects for each MFA type configured in the system
            $returnValue = [
                'EmailMfaSettings' => [
                    'Enabled' => ($isMfaEnabled && $isEnable)?(in_array('EMAIL_MFA', $listMfaTypes)):false,
                    'PreferredMfa' => (($preferredMfaType=='EMAIL_MFA') && ($isEnable && $isMfaEnabled))
                ],
                'SMSMfaSettings' => [
                    'Enabled' => ($isMfaEnabled && $isEnable)?(in_array('SMS_MFA', $listMfaTypes)):false,
                    'PreferredMfa' => (($preferredMfaType=='SMS_MFA') && ($isEnable && $isMfaEnabled))
                ],
                'SoftwareTokenMfaSettings' => [
                    'Enabled' => ($isMfaEnabled && $isEnable)?(in_array('SOFTWARE_TOKEN_MFA', $listMfaTypes)):false,
                    'PreferredMfa' => (($preferredMfaType=='SOFTWARE_TOKEN_MFA') && ($isEnable && $isMfaEnabled))
                ],
                'WebAuthnMfaSettings' => [
                    'Enabled' => ($isMfaEnabled && $isEnable)?(in_array('WEB_AUTHN', $listMfaTypes)):false
                ]
            ];
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:buildPreferencePayload:Exception');
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Set user pool MFA configuration.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_SetUserPoolMfaConfig.html
     *
     * @return \Aws\Result
     * @throws \Ellaisys\Cognito\Exceptions\AwsCognitoException
     */
    public function setUserPoolMfaConfig(): AwsResult
    {
        //Initialize variables
        $response = null;

        try {
            // Check if MFA is enabled and get the list of MFA types
            $isMfaEnabled = (config('cognito.mfa_setup') != 'OFF')?true:false;
            $listMfaTypes = explode(',', config('cognito.mfa_type', 'SOFTWARE_TOKEN_MFA'));

            //Build payload
            $payload = [
                'UserPoolId' => $this->poolId,
                'MfaConfiguration' => config('cognito.mfa_setup', 'OFF'),
            ];

            //Add Software Token MFA configuration if enabled and selected in mfa_type
            if ($isMfaEnabled) {
                $payload = array_merge($payload, [
                    'SoftwareTokenMfaConfiguration' => [
                        'Enabled' => (in_array('SOFTWARE_TOKEN_MFA', $listMfaTypes))?true:false,
                    ]
                ]);
            } //End if

            //Add Email MFA configuration if enabled and selected in mfa_type
            if ($isMfaEnabled && in_array('EMAIL_MFA', $listMfaTypes)) {
                $payload = array_merge($payload, [
                    'EmailMfaConfiguration' => config('cognito.email_mfa_configuration')
                ]);
            } //End if

            //Add SMS MFA configuration if enabled and selected in mfa_type
            if ($isMfaEnabled && in_array('SMS_MFA', $listMfaTypes)) {
                $payload = array_merge($payload, [
                    'SmsMfaConfiguration' => config('cognito.sms_mfa_configuration')
                ]);
            } //End if

            //Add WebAuthn  configuration if enabled
            if ($isMfaEnabled && in_array('WEB_AUTHN', $listMfaTypes)) {
                $payload = array_merge($payload, [
                    'WebAuthnMfaConfiguration' => config('cognito.web_authn_mfa_configuration')
                ]);
            } //End if

            $response = $this->client->setUserPoolMfaConfig($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientMFAAction:setUserPoolMfaConfig:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
