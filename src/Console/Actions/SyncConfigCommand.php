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
    protected $signature = 'cognito:sync-config {--up : Sync user pool configuration}
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
                $this->info('Fetching user pool client configuration...');
                $this->getUserPoolClientConfigUpdEnv();
                $this->info('Fetching user pool MFA configuration...');
                $this->getUserPoolMfaConfigUpdEnv();
            } // End if
        } catch (Exception $exception) {
            Log::error('SyncConfigCommand:handle:Exception');
            $this->error('Error syncing configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

    /**
     * Get User Pool configuration and update .env file.
     */
    private function getUserPoolConfigUpdEnv(): void
    {
        try {
            // Get user pool configuration from AWS Cognito
            if ($userPool = $this->getUserPoolConfig()) {
                Log::info(json_encode($userPool, JSON_PRETTY_PRINT));

                $passwordPolicy = $userPool['Policies']['PasswordPolicy'] ?? [];
                $signinPolicy = $userPool['Policies']['SignInPolicy'] ?? [];
                if (empty($passwordPolicy) || empty($signinPolicy)) {
                    throw new Exception('PasswordPolicy or SignInPolicy is empty. Please check your AWS Cognito user pool configuration.');
                } // End if

                // Set the value in .env file (Password Policy - Base 64 encoded data)
                $this->setEnv('AWS_COGNITO_PASSWORD_POLICY', base64_encode(json_encode($passwordPolicy)));

                // Set the value in .env file (Sign In Policy)
                $this->setEnv('AWS_COGNITO_SIGNIN_POLICY', implode(',', $signinPolicy['AllowedFirstAuthFactors'] ?? []));

                // Set the value in .env file
                $this->setEnv('AWS_COGNITO_MFA_SETUP', $userPool['MfaConfiguration'] ?? 'OFF');
            } // End if
        } catch (Exception $exception) {
            Log::error('SyncConfigCommand:getUserPoolConfigUpdEnv:Exception');
            throw new Exception('Error fetching user pool configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

    /**
     * Get User Pool Client configuration and update .env file.
     */
    private function getUserPoolClientConfigUpdEnv(): void
    {
        try {
            // Get user pool client configuration from AWS Cognito
            if ($userPoolClient = $this->getUserPoolClientConfig()) {
                Log::info(json_encode($userPoolClient, JSON_PRETTY_PRINT));

                $explicitAuthFlows = $userPoolClient['ExplicitAuthFlows'] ?? [];
                if (empty($explicitAuthFlows)) {
                    throw new Exception('ExplicitAuthFlows is empty. Please check your AWS Cognito user pool client configuration.');
                } // End if

                //Set the value in .env file
                $this->setEnv('AWS_COGNITO_ALLOWED_AUTH_FLOWS', implode(',', $explicitAuthFlows));

                // Check for AuthFlowTypes
                $allowPasskeys = (in_array('ALLOW_' . Enums\CognitoAuthFlowTypes::USER_AUTH->value, $explicitAuthFlows));

                //Set the value in .env file
                $this->setEnv('AWS_COGNITO_ALLOW_PASSKEYS', $allowPasskeys ? true : false);

                $accessTokenValidity = $userPoolClient['AccessTokenValidity'] ?? 60; // Default to 60 minutes if not set
                $multiplyFactor = $userPoolClient['TokenValidityUnits']['AccessToken'] ?? 'minutes'; // Default to minutes if not set
                if ($multiplyFactor === 'hours') {
                    $accessTokenValidity *= 60; // Convert hours to minutes
                } elseif ($multiplyFactor === 'days') {
                    $accessTokenValidity *= 1440; // Convert days to minutes
                } // End if

                //Set the value in .env file
                $this->setEnv('SESSION_LIFETIME', $accessTokenValidity);
                $this->setEnv('AUTH_PASSWORD_TIMEOUT', $accessTokenValidity*60); // Convert minutes to seconds

                // Set the value in .env file for token revocation
                $enableTokenRevocation = $userPoolClient['EnableTokenRevocation'] ?? true; // Default to true if not set
                $this->setEnv('AWS_COGNITO_ENABLE_TOKEN_REVOCATION', $enableTokenRevocation ? true : false);

                // Set the value in .env file for Auth Session Validity
                $authSessionValidity = $userPoolClient['AuthSessionValidity'] ?? 3;
                $this->setEnv('AWS_COGNITO_AUTH_SESSION_VALIDITY', ($authSessionValidity * 60)); // Convert minutes to seconds
            } // End if
        } catch (Exception $exception) {
            Log::error('SyncConfigCommand:getUserPoolClientConfigUpdEnv:Exception');
            throw new Exception('Error fetching user pool client configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

    /**
     * Get User Pool MFA configuration and update .env file.
     */
    private function getUserPoolMfaConfigUpdEnv(): void
    {
        try {
            // Get user pool MFA configuration from AWS Cognito
            if ($userPoolMfaConfig = $this->getUserPoolMfaConfig()) {
                Log::info(json_encode($userPoolMfaConfig, JSON_PRETTY_PRINT));

                // Check Software Token MFA configuration
                static $softwareTokenText = 'SOFTWARE_TOKEN_MFA';
                $softwareTokenEnabled = $userPoolMfaConfig['SoftwareTokenMfaConfiguration']['Enabled'] ?? false;
                $mfatypes = explode(',', config('cognito.mfa_type'));

                // Remove SOFTWARE_TOKEN_MFA from mfa_type if it's not enabled
                if (in_array($softwareTokenText, $mfatypes) && !$softwareTokenEnabled) {
                    unset($mfatypes[array_search($softwareTokenText, $mfatypes)]);
                    $this->setEnv('AWS_COGNITO_MFA_TYPE', implode(',', $mfatypes));
                } // End if

                // Add SOFTWARE_TOKEN_MFA to mfa_type if it's enabled and not already present
                if (!in_array($softwareTokenText, $mfatypes) && $softwareTokenEnabled) {
                    $mfatypes[] = $softwareTokenText;
                    $this->setEnv('AWS_COGNITO_MFA_TYPE', implode(',', $mfatypes));
                } // End if

            } // End if
        } catch (Exception $exception) {
            Log::error('SyncConfigCommand:getUserPoolMfaConfigUpdEnv:Exception');
            throw new Exception('Error fetching user pool MFA configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

} // Class ends
