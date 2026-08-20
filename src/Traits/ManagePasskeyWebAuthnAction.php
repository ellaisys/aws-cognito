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

use Ellaisys\Cognito\AwsCognitoClient;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client for Passkey Actions
 */
trait ManagePasskeyWebAuthnAction
{
    /**
     * Starts registration of a passkey authenticator for the currently signed-in user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_StartWebAuthnRegistration.html
     *
     * @param string $accessToken
     *
     * @return AwsResult
     */
    final public function startWebAuthnRegistration(string $accessToken): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken
            ];

            $response = $this->client->startWebAuthnRegistration($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagePasskeyWebAuthnAction:startWebAuthnRegistration:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Completes registration of a passkey authenticator for the currently signed-in user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_CompleteWebAuthnRegistration.html
     *
     * @param string $accessToken
     * @param array $credential A public-key credential response from the user's passkey provider
     *
     * @return AwsResult
     */
    final public function completeWebAuthnRegistration(string $accessToken,
        array $credential): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'Credential' => $credential
            ];

            $response = $this->client->completeWebAuthnRegistration($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagePasskeyWebAuthnAction:completeWebAuthnRegistration:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Lists the passkey authenticators that are registered to the currently signed-in user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListWebAuthnCredentials.html
     *
     * @param string $accessToken
     * @param int|null $maxResults The maximum number of results to return. Default is 20.
     * @param string|null $nextToken A pagination token to retrieve the next set of results.
     *
     * @return AwsResult
     */
    final public function listWebAuthnCredentials(string $accessToken,
        int $maxResults = 20, ?string $nextToken = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'MaxResults' => $maxResults,
                'NextToken' => $nextToken
            ];

            $response = $this->client->listWebAuthnCredentials($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagePasskeyWebAuthnAction:listWebAuthnCredentials:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Deletes a passkey authenticator that is registered to the currently signed-in user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_DeleteWebAuthnCredential.html
     *
     * @param string $accessToken
     * @param string $credentialId The unique identifier of the passkey credential to delete.
     *
     * @return AwsResult
     */
    final public function deleteWebAuthnCredential(string $accessToken,
        string $credentialId): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'CredentialId' => $credentialId
            ];

            $response = $this->client->deleteWebAuthnCredential($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagePasskeyWebAuthnAction:deleteWebAuthnCredential:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Authenticates a user using their passkey credentials. This method initiates
     * the authentication process and returns a challenge that the client must
     * respond to with the appropriate passkey credential response.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html
     * @param CognitoAuthFlowTypes $authFlow Must be either USER_AUTH or CUSTOM_AUTH.
     * @param string $username
     * @param string $challenge
     *
     * @return AwsResult
     */
    final public function authWebAuthnCredential(CognitoAuthFlowTypes $authFlow,
        string $username, ?string $challenge): AwsResult
    {
        try {
            return $this->authenticate($authFlow, $username, null, null, $challenge);
        } catch (Exception $exception) {
            Log::error('ManagePasskeyWebAuthnAction:authWebAuthnCredential:Exception');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends
    
} //Trait ends
