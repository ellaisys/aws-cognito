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

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums;

use Exception;

trait AwsCognitoTrait
{
    /**
     * Get user pool configuration.
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

            return $response->get('MfaConfiguration');

        } catch (Exception $exception) {
            throw $exception;
        } // Try-catch ends
    } //Function ends

} // Trait ends
