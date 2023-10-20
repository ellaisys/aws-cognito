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
    public function attemptBaseMFA(array $challenge = [], Authenticatable $user, bool $remember=false) {
        try {
            $claim = null;

            $challengeName = $challenge['challenge_name'];
            $session = $challenge['session'];
            $challengeValue = $challenge['mfa_code'];
            $username = $challenge['username'];

            $result = $this->client->authMFAChallenge($challengeName, $session, $challengeValue, $username);
            //Result of type AWS Result
            if (!empty($result) && $result instanceof AwsResult) {
                //Check in case of any challenge
                if (isset($result['ChallengeName'])) { 
                    return $result;
                } else {
                    //Create claim token
                    return new AwsCognitoClaim($result, $user, $username);
                } //End if
            } else {
                throw new HttpException(400, 'ERROR_AWS_COGNITO_MFA_CODE_NOT_PROPER');
            } //End if
        } catch(CognitoIdentityProviderException $e) {
            throw new AwsCognitoException('ERROR_AWS_COGNITO_MFA_CODE', $e);
        } catch(Exception $e) {
            throw $e;
        } //Try-catch ends
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
                    $payload = [
                        'SecretCode' => $secretCode,
                        'SecretCodeQR' => 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl='.$uriTotp.'&choe=UTF-8',
                        'TotpUri' => $uriTotp
                    ];
                    return $payload;
                } else {
                    throw new HttpException(400, 'ERROR_AWS_COGNITO_MFA_CODE_NOT_PROPER');
                } //End if
            } else {
                throw new NoTokenException('ERROR_AWS_COGNITO_NO_TOKEN');
            } //End if
        } catch(CognitoIdentityProviderException $e) {
            throw new AwsCognitoException($e->getAwsErrorMessage(), $e);
        } catch(Exception $e) {
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
                    $payload = [
                        'Status' => $response->get('Status')
                    ];
                    return $payload;
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
