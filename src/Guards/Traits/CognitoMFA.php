<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Guards\Traits;

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

use Ellaisys\Cognito\AwsCognitoClaim;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Trait Base Cognito Guard
 */
trait CognitoMFA
{

    /**
     * Attempt MFA based Authentication
     */
    public function attemptBaseMFA(array $challenge = [], bool $remember=false) {
        try {
            //Reset global variables
            $this->challengeName = null;
            $this->challengeData = null;
            $this->claim = null;
            $this->awsResult = null;

            $challengeName = $challenge['challenge_name'];
            $session = $challenge['session'];
            $challengeValue = $challenge['mfa_code'];
            $username = $challenge['username'];

            //Attempt MFA Challenge
            $result = $this->client->authMFAChallenge($challengeName, $session, $challengeValue, $username);

            //Check if the result is an instance of AwsResult
            if (!empty($result) && $result instanceof AwsResult) {
                //Set value into class param
                $this->awsResult = $result;

                //Check in case of any challenge
                if (isset($result['ChallengeName'])) {
                    $this->challengeName = $result['ChallengeName'];
                    $this->challengeData = $this->handleCognitoChallenge($result, $username);
                } elseif (isset($result['AuthenticationResult'])) {
                    //Create claim token
                    $this->claim = new AwsCognitoClaim($result, null);
                } else {
                    throw new HttpException(400, 'ERROR_AWS_COGNITO_MFA_CODE_NOT_PROPER');
                } //End if
            } //End if
    
            return $result;
        } catch(CognitoIdentityProviderException | Exception $e) {
            Log::error('CognitoMFA:attemptBaseMFA:Exception');
            throw $e;
        } //Try-catch ends
            
        return $result;
    } //Function ends


    /**
     * Associate the MFA Software Token
     *
     * @param  string $appName (optional)
     *
     * @return array
     */
    public function associateSoftwareTokenMFA(string $appName=null, string $userParamToAddToQR='email') {
        try {
            //Get Access Token
            //$claim = $this->session->has('claim')?$this->session->get('claim'):null;
            $accessToken = $this->cognito->getToken();
            if (!empty($accessToken)) {
                $response = $this->client->associateSoftwareTokenMFA($accessToken);
                if ($response && ($response instanceof AwsResult) &&
                    isset($response['@metadata']['statusCode']) && $response['@metadata']['statusCode']==200) {

                    //Build payload
                    $secretCode = $response->get('SecretCode');
                    $username = $this->user()[$userParamToAddToQR];
                    $appName = (!empty($appName))?:config('app.name');
                    $uriTotp = 'otpauth://totp/'.$appName.' ('.$username.')?secret='.$secretCode.'&issuer='.config('app.name');
                    return [
                        'SecretCode' => $secretCode,
                        'SecretCodeQR' => config('cognito.mfa_qr_library').$uriTotp,
                        'TotpUri' => $uriTotp
                    ];
                } //End if
            } else {
                throw new NoTokenException('ERROR_AWS_COGNITO_NO_TOKEN');
            } //End if
        } catch(CognitoIdentityProviderException $e) {
            Log::error('CognitoMFA:associateSoftwareTokenMFA:CognitoIdentityProviderException');
            throw new AwsCognitoException($e->getAwsErrorMessage(), 400, $e);
        } catch(Exception $e) {
            Log::error('CognitoMFA:associateSoftwareTokenMFA:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Verify the MFA Software Token
     *
     * @param  string  $guard
     * @param  string  $userCode
     * @param  string  $deviceName (optional)
     *
     * @return array
     */
    public function verifySoftwareTokenMFA(string $userCode, string $deviceName=null) {
        try {
            //Get Access Token
            $accessToken = $this->cognito->getToken();
            if (!empty($accessToken)) {
                $response = $this->client->verifySoftwareTokenMFA($userCode, $accessToken, null, $deviceName);
                if (!empty($response)) {
                    return [
                        'Status' => $response->get('Status')
                    ];
                } //End if
            } else {
                return null;
            } //End if
        } catch(Exception $e) {
            if ($e instanceof CognitoIdentityProviderException) {
                throw new AwsCognitoException($e->getAwsErrorMessage(), $e);
            } //End if
            throw $e;
        } //Try-catch ends
    } //Function ends
    
} //Trait ends
