<?php

namespace Ellaisys\Cognito\Traits;

use Config;
use Carbon\Carbon;

use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Illuminate\Support\Facades\Log;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client for Users (Non-Admin Actions)
 */
trait AwsCognitoClientAction
{
    /**
     * Get user details.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GetUser.html
     *
     * @param string $accessToken
     *
     * @return mixed
     */
    public function getUser(string $accessToken): mixed
    {
        try {
            return $this->client->getUser([
                'AccessToken' => $accessToken
            ]);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientAction:getUser:CognitoIdentityProviderException');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Responds to an authentication challenge
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_RespondToAuthChallenge.html
     *
     * @param CognitoChallengeTypes $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     *
     * @return \Aws\Result
     */
    public function respondToAuthChallenge(
        CognitoChallengeTypes $challengeName, string $session,
        string $challengeValue, string $username)
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'Session' => $session,
                'ChallengeName' => $challengeName->value,
            ];

            //Set challenge response
            $payload['ChallengeResponses'] = $this->buildChallengePayload(
                $challengeName, $challengeValue, $username
            );

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['ChallengeResponses'] = array_merge(
                    $payload['ChallengeResponses'], [
                        'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            //Execute the payload
            $response = $this->client->respondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAction:respondToAuthChallenge:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
