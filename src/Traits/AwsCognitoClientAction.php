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
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

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
     * Declares an authentication flow and initiates sign-in for a user in the Amazon Cognito user directory
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html
     * @param CognitoAuthFlowTypes $authFlow
     * @param array $payloadData
     * @param string $username
     * @return \Aws\Result
     */
    public function initiateAuth(CognitoAuthFlowTypes $authFlow,
        array $payloadData, string $username): \Aws\Result
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => $authFlow->value,
                'ClientId' => $this->clientId
            ];

            $payload = array_merge($payload, $payloadData);

            //Add Secret Hash in case of Client Secret being configured
            $payload = $this->cognitoSecretHash($username, $payload);

            $response = $this->client->initiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAction:initiateAuth:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

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
            $payload = $this->cognitoSecretHash($username, $payload);

            //Execute the payload
            $response = $this->client->respondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientAction:respondToAuthChallenge:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
