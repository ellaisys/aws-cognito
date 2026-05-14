<?php

namespace Ellaisys\Cognito\Traits;

use Config;

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
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_SetUserMFAPreference.html
     *
     * @param string $username
     * @return mixed
     */
    public function setUserMFAPreference(string $accessToken, bool $isEnable=false)
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'UserPoolId' => $this->poolId
            ];
            $payload = array_merge($payload, $this->setMFAPreference($isEnable));

            $response = $this->client->setUserMFAPreference($payload);
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:setUserMFAPreference:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Set user MFA preference setting by admin.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminSetUserMFAPreference.html
     *
     * @param string $username
     *
     * @return mixed
     */
    public function setUserMFAPreferenceByAdmin(string $username, bool $isEnable=false)
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'UserPoolId' => $this->poolId
            ];
            $payload = array_merge($payload, $this->setMFAPreference($isEnable));

            $response = $this->client->adminSetUserMFAPreference($payload);
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:setUserMFAPreferenceByAdmin:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Private method for Setting MFA preference objects
     *
     * @return mixed
     */
    private function setMFAPreference(bool $isEnable)
    {
        try {
            //Build payload
            $payload = [];

            $isMfaEnabled = (config('cognito.mfa_setup') == 'MFA_ENABLED')?true:false;
            $listMfaTypes = explode(',', config('cognito.mfa_type', 'SOFTWARE_TOKEN_MFA'));

            $firstMfaType=null;
            foreach ($listMfaTypes as $mfaType) {
                if (empty($firstMfaType)) { $firstMfaType=$mfaType; }

                //Add Email MFA configuration if enabled and selected in mfa_type
                $payload = array_merge($payload, [
                    'EmailMfaSettings' => [
                        'Enabled' => ($isMfaEnabled && $isEnable)?($mfaType=='EMAIL_MFA'):false,
                        'PreferredMfa' => (($firstMfaType=='EMAIL_MFA') && ($isEnable))
                    ]
                ]);
                
                //Add SMS MFA configuration if enabled and selected in mfa_type
                $payload = array_merge($payload, [
                    'SMSMfaSettings' => [
                        'Enabled' => ($isMfaEnabled && $isEnable)?($mfaType=='SMS_MFA'):false,
                        'PreferredMfa' => (($firstMfaType=='SMS_MFA') && ($isEnable))
                    ]
                ]);

                //Add Software Token MFA configuration if enabled and selected in mfa_type
                $payload = array_merge($payload, [
                    'SoftwareTokenMfaSettings' => [
                        'Enabled' => ($isMfaEnabled && $isEnable)?($mfaType=='SOFTWARE_TOKEN_MFA'):false,
                        'PreferredMfa' => (($firstMfaType=='SOFTWARE_TOKEN_MFA') && ($isEnable))
                    ]
                ]);

                //Add WebAuthn configuration if enabled
                $payload = array_merge($payload, [
                    'WebAuthnMfaSettings' => [
                        'Enabled' => ($isMfaEnabled && $isEnable)?($mfaType=='WEB_AUTHN'):false
                    ]
                ]);
            } //Loop ends

            $response = $payload;
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:setMFAPreference:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Set user pool MFA configuration.
      * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_SetUserPoolMfaConfig.html
      *
      * @param string $mfaConfiguration
      */
    public function setUserPoolMfaConfig(?string $mfaConfiguration = 'OPTIONAL')
    {
        try {
            $isMfaEnabled = (config('cognito.mfa_setup') == 'MFA_ENABLED')?true:false;
            $listMfaTypes = explode(',', config('cognito.mfa_type', 'SOFTWARE_TOKEN_MFA'));

            //Build payload
            $payload = [
                'UserPoolId' => $this->poolId,
                'MfaConfiguration' => $mfaConfiguration,
                'SoftwareTokenMfaConfiguration' => [
                    'Enabled' => ($isMfaEnabled && in_array('SOFTWARE_TOKEN_MFA', $listMfaTypes))?true:false,
                ]
            ];

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
        } catch (Exception $e) {
            Log::error('AwsCognitoClientMFAAction:setUserPoolMfaConfig:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
