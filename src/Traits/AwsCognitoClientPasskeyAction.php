<?php

namespace Ellaisys\Cognito\Traits;

use Config;

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

    /**
     * Authenticates a user using their passkey credentials. This method initiates
     * the authentication process and returns a challenge that the client must
     * respond to with the appropriate passkey credential response.
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html
     * @param CognitoAuthFlowTypes $authFlow Must be either USER_AUTH or CUSTOM_AUTH.
     * @param string $username
     * @param string $challenge
     * @return \Aws\Result
     */
    public function authWebAuthnCredential(CognitoAuthFlowTypes $authFlow,
        string $username, ?string $challenge)
    {
        try {
            //Build payload
            $payload = [
                'AuthFlow' => $authFlow->value,
                'ClientId' => $this->clientId,
                'UserPoolId' => $this->poolId,
            ];

            //Set Auth Parameters based on the Auth Flow
            switch ($authFlow) {
                case CognitoAuthFlowTypes::USER_AUTH:
                    $payload['AuthParameters'] = [
                        'USERNAME' => $username,
                        'PREFERRED_CHALLENGE' => $challenge
                    ];
                    break;

                case CognitoAuthFlowTypes::CUSTOM_AUTH:
                default:
                    $payload['AuthParameters'] = [
                        'USERNAME' => $username
                    ];
                    break;
            } //End switch

            //Add Secret Hash in case of Client Secret being configured
            if ($this->boolClientSecret) {
                $payload['AuthParameters'] = array_merge($payload['AuthParameters'], [
                    'SECRET_HASH' => $this->cognitoSecretHash($username)
                ]);
            } //End if

            $response = $this->client->initiateAuth($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientPasskeyAction:authWebAuthnCredential:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Responds to the passkey authentication challenges with the user's passkey credential response.
     * TO BE DELETED
     *
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_RespondToAuthChallenge.html
     * @param string $challengeName
     * @param string $session
     * @param string $challengeValue
     * @param string $username
     * @return \Aws\Result
     */
    public function respondToWebAuthnChallenge(
        CognitoChallengeTypes $challengeName,
        string $session, string $challengeValue, string $username)
    {
        try {
            switch ($challengeName) {
                case CognitoChallengeTypes::WEB_AUTHN:
                case CognitoChallengeTypes::EMAIL_OTP:
                case CognitoChallengeTypes::SMS_OTP:
                     $response = $this->adminRespondToAuthChallenge(
                        $challengeName, $session, $challengeValue, $username
                    );
                    break;

                default:
                    throw new HttpException(400, 'ERROR_UNSUPPORTED_MFA_CHALLENGE');
            } //End switch
        } catch (Exception $e) {
            Log::error('AwsCognitoClientPasskeyAction:respondToWebAuthnChallenge:Exception');
            throw $e;
        } //Try-catch ends

        return $response;
    } //Function ends
    
} //Trait ends
