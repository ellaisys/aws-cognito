<?php

namespace Ellaisys\Cognito\Traits;

use Config;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client for Passkey Actions
 */
trait AwsCognitoClientPasskeyAction
{
    /**
     * Starts registration of a passkey authenticator for the currently signed-in user.
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#startwebauthnregistration
     *
     * @param string $accessToken
     *
     * @return mixed
     */
    public function startWebAuthnRegistration(string $accessToken)
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken
            ];

            $response = $this->client->startWebAuthnRegistration($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientPasskeyAction:startWebAuthnRegistration:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Completes registration of a passkey authenticator for the currently signed-in user.
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#completewebauthnregistration
     *
     * @param string $accessToken
     * @param mixed $credential A public-key credential response from the user's passkey provider
     *
     * @return mixed
     */
    public function completeWebAuthnRegistration(string $accessToken, array $credential)
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'Credential' => $credential
            ];

            $response = $this->client->completeWebAuthnRegistration($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientPasskeyAction:completeWebAuthnRegistration:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
    * Lists the passkey authenticators that are registered to the currently signed-in user.
    * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#listwebauthncredentials
    *
    * @param string $accessToken
    * @param int|null $maxResults The maximum number of results to return. Default is 20.
    * @param string|null $nextToken A pagination token to retrieve the next set of results.
    *
    * @return mixed
    */
    public function listWebAuthnCredentials(string $accessToken, 
        ?int $maxResults = 20, ?string $nextToken = null)
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
            Log::error('AwsCognitoClientPasskeyAction:listWebAuthnCredentials:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
    * Deletes a passkey authenticator that is registered to the currently signed-in user.
    * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html#deletewebauthncredential
    *
    * @param string $accessToken
    * @param string $credentialId The unique identifier of the passkey credential to delete.
    *
    * @return mixed
    */
    public function deleteWebAuthnCredential(string $accessToken, string $credentialId)
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'CredentialId' => $credentialId
            ];

            $response = $this->client->deleteWebAuthnCredential($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientPasskeyAction:deleteWebAuthnCredential:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends
    
} //Trait ends
