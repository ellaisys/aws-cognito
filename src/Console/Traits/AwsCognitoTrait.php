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
     * Get user pool configuration.
     *
     * @return array
     */
    protected function getUserPoolConfig(): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool configuration
            $response = $client->describeUserPool();

            return $response->get('UserPool');
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolConfig:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool client configuration
     *
     * @return array
     */
    protected function getUserPoolClientConfig(): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool client configuration
            $response = $client->describeUserPoolClient();

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
    protected function getUserPoolMfaConfig(): array
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool MFA configuration
            $response = $client->getUserPoolMfaConfig();

            return $response->toArray();
        } catch (Exception $exception) {
            Log::error('AwsCognitoTrait:getUserPoolMfaConfig:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

} // Trait ends
