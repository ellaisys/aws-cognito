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


use Ellaisys\Cognito\AwsCognito;
use Illuminate\Contracts\Auth\Authenticatable;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClientInterface;
use Ellaisys\Cognito\AwsCognitoClientManager;

/**
 * Trait Base Cognito Guard
 */
trait BaseCognitoGuard
{

    /**
	 * Get the AWS Cognito object
     * 
	 * @return \Ellaisys\Cognito\AwsCognito
	 */
    public function cognito() {
        return $this->cognito;
    } //Function ends


    /**
	 * Get the User Information from AWS Cognito
     * 
	 * @return  mixed
	 */
    public function getRemoteUserData(string $username) {
        return $this->client->getUser($username);
    } //Function ends


    /**
     * Validate the user credentials with AWS Cognito
     *
     * @return \Ellaisys\Cognito\AwsCognitoClient
     */
    protected function hasValidCredentials($credentials) {
        try {
            //Authenticate the user with AWS Cognito
            $result = $this->client->authenticate($credentials['email'], $credentials['password']);

            //Check if the result is an instance of AwsResult
            if (!empty($result) && $result instanceof AwsResult) {
                //Set value into class param
                $this->awsResult = $result;
    
                //Check in case of any challenge
                if (isset($result['ChallengeName'])) {
                    $this->challengeName = $result['ChallengeName'];
                    $this->challengeData = $this->handleCognitoChallenge($result, $credentials['email']);
                } //End if
            } //End if
    
            return $result;
        } catch (Exception $e) {
            throw $e;
        } //End try-catch

    } //Function ends


    /**
     * handle Cognito Challenge
     */
    protected function handleCognitoChallenge(AwsResult $result, string $username) {

        //Return value
        $returnValue = null;

        //Set challenge into class param
        $this->challengeName = $result['ChallengeName'];
        
        switch ($result['ChallengeName']) {
            case 'SOFTWARE_TOKEN_MFA':
                $returnValue = [
                    'status' => $result['ChallengeName'],
                    'session_token' => $result['Session'],
                    'username' => $username,
                    'user' => serialize($user)
                ];
                break;

            case 'SMS_MFA':
                $returnValue = [
                    'status' => $result['ChallengeName'],
                    'session_token' => $result['Session'],
                    'challenge_params' => $result['ChallengeParameters'],
                    'username' => $username,
                    'user' => serialize($user)
                ];
                break;

            default:
                if (in_array($result['ChallengeName'], config('cognito.forced_challenge_names'))) {
                    $returnValue = $result['ChallengeName'];
                } //End if
                break;
        } //End switch

        return $returnValue;
    } //Function ends

} //Trait ends
