<?php

namespace Ellaisys\Cognito\Traits;

use Config;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
    public function associateSoftwareTokenMFA(string $accessToken=null, string $session=null)
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
    public function verifySoftwareTokenMFA(string $userCode, string $accessToken=null, string $session=null, string $deviceName=null)
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
            Log::info(json_encode($payload, JSON_PRETTY_PRINT));

            $response = $this->client->adminSetUserMFAPreference($payload);
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends


    /**
     * Responds to MFA challenge
     *
     * @param string $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     *  
     * @return \Aws\Result|false
     */
    public function authMFAChallenge(string $challengeName, string $session, string $challengeValue, string $username)
    {
        try {
            if (in_array($challengeName, [AwsCognitoClient::SMS_MFA, AwsCognitoClient::SOFTWARE_TOKEN_MFA])) {
                $response = $this->adminRespondToAuthChallenge($challengeName, $session, $challengeValue, $username);
            } else {
                throw new HttpException(400, 'ERROR_UNSUPPORTED_MFA_CHALLENGE');
            } //End if
        } catch (Exception $e) {
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

            $mfaTypes = explode(',', config('cognito.mfa_type', 'SOFTWARE_TOKEN_MFA'));
            $firstMfaType=null;
            foreach ($mfaTypes as $mfaType) {
                if (empty($firstMfaType)) { $firstMfaType=$mfaType; }
                
                $payload = array_merge($payload, [
                    'SMSMfaSettings' => [
                        'Enabled' => (config('cognito.mfa_setup', 'MFA_NONE')=='MFA_ENABLED')?($mfaType=='SMS_MFA'):false,
                        'PreferredMfa' => ($firstMfaType=='SMS_MFA')
                    ]
                ]);

                $payload = array_merge($payload, [
                    'SoftwareTokenMfaSettings' => [
                        'Enabled' => (config('cognito.mfa_setup', 'MFA_NONE')=='MFA_ENABLED')?($mfaType=='SOFTWARE_TOKEN_MFA'):false,
                        'PreferredMfa' => ($firstMfaType=='SOFTWARE_TOKEN_MFA')
                    ]
                ]);
            } //Loop ends

            $response = $payload;
        } catch (Exception $e) {
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends