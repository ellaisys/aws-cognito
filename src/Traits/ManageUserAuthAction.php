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

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Manages AWS Cognito Client for User Auth Actions (Non-Admin Actions)
 */
trait ManageUserAuthAction
{
    /**
     * Declares an authentication flow and initiates sign-in for a user in the Amazon Cognito user directory
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html
     * @param \CognitoAuthFlowTypes $authFlow
     * @param array $payloadData
     * @param string $username
     * @return \AwsResult
     */
    final public function initiateAuth(CognitoAuthFlowTypes $authFlow,
        array $payloadData, string $username): AwsResult
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

            return $this->client->initiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserAuthAction:initiateAuth:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Get user details.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GetUser.html
     *
     * @param string $accessToken
     *
     * @return \AwsResult
     */
    final public function getUser(string $accessToken): AwsResult
    {
        try {
            return $this->client->getUser([
                'AccessToken' => $accessToken
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserAuthAction:getUser:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } // Function ends

    /**
     * Responds to an authentication challenge
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_RespondToAuthChallenge.html
     *
     * @param \CognitoChallengeTypes $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     *
     * @return \AwsResult
     */
    final public function respondToAuthChallenge(
        CognitoChallengeTypes $challengeName, string $session,
        string $challengeValue, string $username): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'ClientId' => $this->clientId,
                'ChallengeName' => $challengeName->value,
            ];

            //Set session for challenge types that require it
            if (!in_array($challengeName, [
                    CognitoChallengeTypes::PASSWORD_VERIFIER,
                    CognitoChallengeTypes::DEVICE_PASSWORD_VERIFIER
                ], true))
            {
                $payload['Session'] = $session;
            } //End if

            //Set challenge response
            $payload['ChallengeResponses'] = $this->buildChallengePayload(
                $challengeName, $challengeValue, $username
            );

            //Add Secret Hash in case of Client Secret being configured
            $payload = $this->cognitoSecretHash($username, $payload);

            //Execute the payload
            return $this->client->respondToAuthChallenge($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageUserAuthAction:respondToAuthChallenge:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } // Function ends

    /**
     * Update a user's attributes.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_UpdateUserAttributes.html
     *
     * @param string $accessToken
     * @param array $attributes
     * @param array|null $clientMetadata
     * @return \Aws\Result
     * @throws \AwsCognitoException
     */
    final public function updateUserAttributes(string $accessToken,
        array $attributes, ?array $clientMetadata = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'UserAttributes' => $this->formatAttributes($attributes),
            ];

            //Set Client Metadata
            if (!empty($clientMetadata)) {
                $payload['ClientMetadata'] = $this->buildClientMetadata([], $clientMetadata);
            } //End if

            //Execute the payload
            return $this->client->updateUserAttributes($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClient:updateUserAttributes:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //End try
    } // Function ends
} // Trait ends
