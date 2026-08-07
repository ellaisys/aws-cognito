<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Console\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Console\Traits\UtilsTrait;
use Ellaisys\Cognito\Console\Traits\AwsCognitoTrait;

use Exception;

class SyncConfigCommand extends Command
{
    use AwsCognitoTrait;
    use UtilsTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:sync-config {--up: Sync user pool configuration}
                                {--down : Sync user pool client configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user pool configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            if (!array_filter($this->options())) {
                $this->error('Please provide at least one option: --up or --down');
                return;
            }

            if ($this->option('down')) {
                $this->info('Fetching user pool configuration...');
                $this->getUserPoolConfigUpdEnv();
            }

            $this->info($returnValue['message'] ?? 'Configuration:');
            $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            Log::error('SyncConfigCommand:handle:Exception');
            $this->error('Error syncing configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

    /**
     * Get user pool configuration and update .env file.
     */
    private function getUserPoolConfigUpdEnv(): void
    {
        try {
            // Get user pool configuration from AWS Cognito
            if ($userPool = $this->getUserPoolConfig()) {
                // Set the value in .env file
                $this->setEnv('AWS_COGNITO_MFA_SETUP', $userPool['MfaConfiguration'] ?? 'OFF');                
            } // End if

            // Get user pool client configuration from AWS Cognito
            if ($userPoolClient = $this->getUserPoolClientConfig()) {
                $explicitAuthFlows = $userPoolClient['ExplicitAuthFlows'] ?? [];

                // Check for AuthFlowTypes
                $allowPasskeys = (in_array(Enums\CognitoAuthFlowTypes::USER_AUTH->value, $explicitAuthFlows));

                //Set the value in .env file
                $this->setEnv('AWS_COGNITO_ALLOW_PASSKEYS', $allowPasskeys ? true : false);

                $accessTokenValidity = $userPoolClient['AccessTokenValidity'] ?? 60; // Default to 60 minutes if not set
                $multiplyFactor = $userPoolClient['TokenValidityUnits']['AccessToken'] ?? 'minutes'; // Default to minutes if not set
                if ($multiplyFactor === 'hours') {
                    $accessTokenValidity *= 60; // Convert hours to minutes
                } elseif ($multiplyFactor === 'days') {
                    $accessTokenValidity *= 1440; // Convert days to minutes
                }

                //Set the value in .env file
                $this->setEnv('SESSION_LIFETIME', $accessTokenValidity);
                $this->setEnv('AUTH_PASSWORD_TIMEOUT', $accessTokenValidity*60); // Convert minutes to seconds
            } // End if

            // Get user pool MFA configuration from AWS Cognito
            if ($userPoolMfaConfig = $this->getUserPoolMfaConfig()) {
                Log::info(json_encode($userPoolMfaConfig, JSON_PRETTY_PRINT));
            } // End if
        } catch (Exception $exception) {
            Log::error('SyncConfigCommand:getUserPoolConfigUpdEnv:Exception');
            throw new Exception('Error fetching user pool configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

} // Class ends
