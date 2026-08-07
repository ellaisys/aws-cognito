<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Console\Traits\UtilsTrait;

use Exception;

class PoolCommand extends Command
{
    use UtilsTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:list-pool-setting {--config : Get user pool configuration}
                                {--client-config : Get user pool client configuration}
                                {--mfa-config : Get user pool MFA configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pool setting description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!array_filter($this->options())) {
            $this->error('Please provide at least one option: --config, --client-config, or --mfa-config');
            return;
        }

        if ($this->option('config')) {
            $this->getUserPoolConfig();
        }

        if ($this->option('client-config')) {
            $this->getUserPoolClientConfig();
        }

        if ($this->option('mfa-config')) {
            $this->getUserPoolMfaConfig();
        }
    }

    /**
     * Get user pool configuration.
     */
    private function getUserPoolConfig()
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool configuration
            $response = $client->describeUserPool();

            if ($userPoolClient = $response->get('UserPool')) {
                //Set the value in .env file
                $this->setEnv('AWS_COGNITO_MFA_SETUP', $userPoolClient['MfaConfiguration'] ?? 'OFF');
            } //End if

            $this->info('User Pool Configuration:');
            $this->info(json_encode($response->toArray(), JSON_PRETTY_PRINT));

        } catch (Exception $exception) {
            $this->error('Error retrieving user pool configuration.');
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool client configuration.
     */
    private function getUserPoolClientConfig()
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool client configuration
            $response = $client->describeUserPoolClient();

            $this->info('User Pool Client Configuration:');
            $this->info(json_encode($response->toArray(), JSON_PRETTY_PRINT));

        } catch (Exception $exception) {
            $this->error('Error retrieving user pool client configuration.');
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool MFA configuration.
     */
    private function getUserPoolMfaConfig()
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool MFA configuration
            $response = $client->getUserPoolMfaConfig();

            $this->info('User Pool MFA Configuration:');
            $this->info(json_encode($response->toArray(), JSON_PRETTY_PRINT));

        } catch (Exception $exception) {
            $this->error('Error retrieving user pool MFA configuration.');
        } // Try-catch ends
    } //Function ends

} // Class ends
