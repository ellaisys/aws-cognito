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

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Manages AWS Cognito Client for User Pool Actions
 */
trait ManagesUserPoolAction
{
    /**
     * List user pools.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListUserPools.html
     *
     * @param int $maxResults (default: 10, max: 60)
     * @param string|null $nextToken
     *
     * @return \AwsResult
     */
    final public function listUserPools(int $maxResults = 10,
        ?string $nextToken = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'MaxResults' => $maxResults
            ];
            if (!is_null($nextToken)) {
                $payload['NextToken'] = $nextToken;
            } //End if

            return $this->client->listUserPools($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:listUserPools:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Get user pool details.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_DescribeUserPool.html
     *
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function describeUserPool(?string $userPoolId = null): AwsResult
    {
        try {
            return $this->client->describeUserPool([
                'UserPoolId' => $userPoolId ?? $this->poolId
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:describeUserPool:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Create a new user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_CreateUserPool.html
     *
     * @param string $poolName
     *
     * @return \AwsResult
     */
    final public function createUserPool(string $poolName): AwsResult
    {
        try {
            $payload = [
                'PoolName' => $poolName,
                'Policies' => [
                    'PasswordPolicy' => config('cognito.password_policy', [
                        'MinimumLength' => 8,
                        'RequireUppercase' => true,
                        'RequireLowercase' => true,
                        'RequireNumbers' => true,
                        'RequireSymbols' => true,
                        'TemporaryPasswordValidityDays' => 7
                    ]),
                    'SignInPolicy' => [
                        'AllowedFirstAuthFactors' => config('cognito.signin_policy', ['PASSWORD']),
                    ]
                ],
                'AutoVerifiedAttributes' => ['email'],
                'Schema' => [
                    [
                        'Name' => 'email',
                        'AttributeDataType' => 'String',
                        'Required' => true
                    ]
                ],
                'AdminCreateUserConfig' => [
                    'AllowAdminCreateUserOnly' => !config('cognito.registration_enabled', true),
                ],
                'MfaConfiguration' => config('cognito.mfa_setup', 'OFF'),
                'UsernameConfiguration' => [
                    'CaseSensitive' => false
                ],
                'UsernameAttributes' => config('cognito.sign_in_username_attributes', ['email']),
                'UserPoolTags' => [
                    'Project' => config('app.name', 'AWS Cognito'),
                    'Environment' => config('app.env', 'Development'),
                    'CreatedBy' => 'AWS Cognito Laravel Package'
                ],
            ];

            // If MFA is enabled, then add the SMS configuration to the payload
            if (config('cognito.mfa_setup', 'OFF') !== 'OFF') {
                $payload['SmsConfiguration'] = config('cognito.sms_mfa_configuration.SmsConfiguration');
            } //End if

            return $this->client->createUserPool($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:createUserPool:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Update user pool details.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_UpdateUserPool.html
     *
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function updateUserPool(?string $userPoolId = null): AwsResult
    {
        try {
            $payload = [
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'Policies' => [
                    'PasswordPolicy' => config('cognito.password_policy', [
                        'MinimumLength' => 8,
                        'RequireUppercase' => true,
                        'RequireLowercase' => true,
                        'RequireNumbers' => true,
                        'RequireSymbols' => true,
                        'TemporaryPasswordValidityDays' => 7
                    ]),
                    'SignInPolicy' => [
                        'AllowedFirstAuthFactors' => config('cognito.signin_policy', ['PASSWORD']),
                    ]
                ],
                'AdminCreateUserConfig' => [
                    'AllowAdminCreateUserOnly' => !config('cognito.registration_enabled', true),
                ],
                'MfaConfiguration' => config('cognito.mfa_setup', 'OFF'),
                'UsernameAttributes' => config('cognito.sign_in_username_attributes', ['email']),
            ];

            return $this->client->updateUserPool($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:updateUserPool:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Delete user pool.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_DeleteUserPool.html
     *
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function deleteUserPool(?string $userPoolId = null): AwsResult
    {
        try {
            return $this->client->deleteUserPool([
                'UserPoolId' => $userPoolId ?? $this->poolId
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:deleteUserPool:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * List user pool clients.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListUserPoolClients.html
     *
     * @param int $maxResults (default: 10, max: 60)
     * @param string|null $nextToken
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function listUserPoolClients(int $maxResults = 10,
        ?string $nextToken = null, ?string $userPoolId = null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'MaxResults' => $maxResults
            ];
            if (!is_null($nextToken)) {
                $payload['NextToken'] = $nextToken;
            } //End if

            return $this->client->listUserPoolClients($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:listUserPoolClients:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Get user pool client details.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_DescribeUserPoolClient.html
     *
     * @return \AwsResult
     */
    final public function describeUserPoolClient(
        ?string $userPoolId = null, ?string $clientId = null): AwsResult
    {
        try {
            return $this->client->describeUserPoolClient([
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'ClientId' => $clientId ?? $this->clientId
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:describeUserPoolClient:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Create user pool client.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_CreateUserPoolClient.html
     *
     * @param string $clientName
     * @param string|null $userPoolId
     *
     * @return \AwsResult
     */
    final public function createUserPoolClient(string $clientName,
        ?string $userPoolId = null): AwsResult
    {
        try {
            $payload = [
                'ClientName' => $clientName,
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'GenerateSecret' => config('cognito.app_client_secret_allow', true),
                'ExplicitAuthFlows' => config('cognito.allowed_auth_flows', [
                    'ALLOW_USER_PASSWORD_AUTH', 'ALLOW_REFRESH_TOKEN_AUTH'
                ]),
                'AllowedOAuthFlows' => ['code'],
                'AllowedOAuthScopes' => [
                    'email', 'openid'
                ],
                'CallbackURLs' => [
                    config('app.url', 'http://localhost')
                ],
                'SupportedIdentityProviders' => ['COGNITO'],
            ];

            return $this->client->createUserPoolClient($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:createUserPoolClient:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

    /**
     * Delete user pool client.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_DeleteUserPoolClient.html
     *
     * @param string|null $userPoolId
     * @param string|null $clientId
     *
     * @return \AwsResult
     */
    final public function deleteUserPoolClient(?string $userPoolId = null, ?string $clientId = null): AwsResult
    {
        try {
            return $this->client->deleteUserPoolClient([
                'UserPoolId' => $userPoolId ?? $this->poolId,
                'ClientId' => $clientId ?? $this->clientId
            ]);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:deleteUserPoolClient:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends
    } //Function ends

} //Trait ends
