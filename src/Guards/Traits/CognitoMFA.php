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
    public function attemptMFA(array $challenge = [], Authenticatable $user, bool $remember=false) {
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

                } else {
                    //Create claim token
                    $claim = new AwsCognitoClaim($result, $user, $username);

                    //Get Session and store details
                    $session = $this->getSession();
                    $session->forget($challenge['session']);
                    $session->put('claim', json_decode(json_encode($claim), true));

                    //Login user into the session
                    $this->login($user, $remember);

                    //Fire successful attempt
                    $this->fireValidatedEvent($user);
                    $this->fireAuthenticatedEvent($user);
                    
                    return true;
                } //End if
            } else {
                throw new HttpException(400, 'ERROR_AWS_COGNITO_MFA_CODE_NOT_PROPER');
            } //End if
        } catch(CognitoIdentityProviderException $e) {
            throw $e;
        } catch(Exception $e) {
            throw $e;
        } //Try-catch ends
    } //Function ends
    
} //Trait ends