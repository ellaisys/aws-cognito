<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Console\Traits;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums;

use Exception;

trait AwsCognitoTrait
{
    /**
     * Get list of user pools.
     *
     * @param int $maxResults
     * @param string|null $nextToken
     *
     * @return array
     */
    final protected function getUserPools(int $maxResults = 10,
        ?string $nextToken = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //List user pools
            $response = $client->listUserPools($maxResults, $nextToken);

            return $response->get('UserPools');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPools:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool configuration.
     *
     * @param string|null $userPoolId
     *
     * @return array
     */
    final protected function getUserPoolConfig(?string $userPoolId = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool configuration
            $response = $client->describeUserPool($userPoolId);

            return $response->get('UserPool');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolConfig:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Create user pool.
     *
     * @param string $poolName
     *
     * @return array
     */
    final protected function createUserPool(string $poolName): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Create user pool
            $response = $client->createUserPool($poolName);

            return $response->get('UserPool');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:createUserPool:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get list of user pool clients.
     *
     * @param string|null $userPoolId
     * @param int $maxResults
     * @param string|null $nextToken
     *
     * @return array
     */
    final protected function getUserPoolClients(?string $userPoolId = null,
        int $maxResults = 10, ?string $nextToken = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //List user pool clients
            $response = $client->listUserPoolClients($maxResults, $nextToken, $userPoolId);

            return $response->get('UserPoolClients');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolClients:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool client configuration
     *
     * @param string|null $userPoolId
     * @param string|null $clientId
     *
     * @return array
     */
    final protected function getUserPoolClientConfig(
        ?string $userPoolId = null, ?string $clientId = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool client configuration
            $response = $client->describeUserPoolClient($userPoolId, $clientId);

            return $response->get('UserPoolClient');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolClientConfig:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool MFA configuration.
     *
     * @return array
     */
    final protected function getUserPoolMfaConfig(?string $userPoolId = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool MFA configuration
            $response = $client->getUserPoolMfaConfig($userPoolId);

            return $response->toArray();
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolMfaConfig:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get list of user pool groups.
     *
     * @param string|null $userPoolId
     * @param int $maxResults
     * @param string|null $nextToken
     *
     * @return array
     */
    final protected function getUserPoolGroups(?string $userPoolId = null,
        int $maxResults = 10, ?string $nextToken = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //List user pool groups
            $response = $client->listGroups($maxResults, $nextToken, $userPoolId);

            return $response->get('Groups');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolGroups:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Create default group in a user pool.
     *
     * @param string|null $userPoolId
     *
     * @return array
     */
    final protected function createDefaultGroup(?string $userPoolId = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool group configuration
            $response = $client->getGroup('default', 'Default Group', null, null, $userPoolId);

            return $response->get('Group');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolGroupConfig:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get list of user pool terms.
     *
     * @param string|null $userPoolId
     * @param int $maxResults
     * @param string|null $nextToken
     *
     * @return array
     */
    final protected function getUserPoolTerms(?string $userPoolId = null,
        int $maxResults = 10, ?string $nextToken = null): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //List user pool terms
            $response = $client->listTerms($maxResults, $nextToken, $userPoolId);

            return $response->get('Terms');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolTerms:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

} // Trait ends
