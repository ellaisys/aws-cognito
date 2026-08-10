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
                    'PasswordPolicy' => [
                        'MinimumLength' => 8,
                        'RequireUppercase' => true,
                        'RequireLowercase' => true,
                        'RequireNumbers' => true,
                        'RequireSymbols' => true
                    ],
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
                'UsernameAttributes' => ['email']
            ];

            return $this->client->createUserPool($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManagesUserPoolAction:createUserPool:CognitoIdentityProviderException');
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

} //Trait ends
