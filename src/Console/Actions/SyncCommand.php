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
use Ellaisys\Cognito\Exceptions\ConsoleException;

class SyncCommand extends Command
{
    use AwsCognitoTrait;
    use UtilsTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:sync
                                {--local-to-aws : Sync configuration from local .env file to AWS}
                                {--aws-to-local : Sync configuration from AWS to local .env file}
                                {--pool : Sync user pool configuration}
                                {--client : Sync user pool client configuration}
                                {--mfa : Sync user pool MFA configuration}
                                {--pool-id= : The user pool ID}
                                {--client-id= : The user pool client ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize AWS Cognito configurations';

    /**
     * The done message.
     *
     * @var string
     */
    const DONE = '✓ DONE';

    /**
     * The user pool ID.
     *
     * @var string|null
     */
    private ?string $userPoolId = null;

    /**
     * The user pool client ID.
     *
     * @var string|null
     */
    private ?string $clientId = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize return value
        $returnValue = Command::SUCCESS;

        try {
            //Check if at least one option is provided
            $needles = ['local-to-aws', 'aws-to-local'];
            $haystack = array_filter($this->options());
            if (empty(array_intersect($needles, array_keys($haystack)))) {
                throw new ConsoleException('Provide at least one option: --local-to-aws or --aws-to-local');
            } //End if

            // Check if options are provided
            $needles = ['pool', 'client', 'mfa'];
            $haystack = array_filter($this->options());
            if (empty(array_intersect($needles, array_keys($haystack)))) {
                $this->input->setOption('pool', true);
                $this->input->setOption('client', true);
                $this->input->setOption('mfa', true);
            } //End if

            $this->newLine();
            $this->info('Sync the configurations.');
            $this->newLine();

            // Get the user pool ID from the option or from the .env file
            $this->userPoolId = $this->option('pool-id') ?: null;

            // Get the user pool client ID from the option or from the .env file
            $this->clientId = $this->option('client-id') ?: null;

            if ($this->option('aws-to-local')) {
                $returnValue = $this->syncAwsToLocal();
            } // End if

            if ($this->option('local-to-aws')) {
                $returnValue = $this->syncLocalToAws();
            } // End if

            $returnValue = Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('SyncCommand:handle:Exception');
            $this->components->error($exception->getMessage());
            $returnValue = Command::FAILURE;
        } // Try-catch ends
        return $returnValue;
    } //Function ends

    /**
     * Sync configuration from AWS to local .env file.
     *
     * @return int
     */
    private function syncAwsToLocal(): int
    {
        // Initialize return value
        $returnValue = Command::SUCCESS;

        try {
            if ( $this->option('pool')) {
                $this->newLine();
                $this->info('Fetching user pool configuration...');
                $returnValue = $this->getUserPoolConfigUpdEnv();
                $this->info(self::DONE);
            } // End if

            if ($this->option('client')) {
                $this->newLine();
                $this->info('Fetching user pool client configuration...');
                $returnValue = $this->getUserPoolClientConfigUpdEnv();
                $this->info(self::DONE);
            } // End if

            if ($this->option('mfa')) {
                $this->newLine();
                $this->info('Fetching user pool MFA configuration...');
                $returnValue = $this->getUserPoolMfaConfigUpdEnv();
                $this->info(self::DONE);
                $this->newLine();
            } // End if
        } catch (Exception $exception) {
            Log::error('SyncCommand:syncAwsToLocal:Exception');
            throw $exception;
        } // Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Get User Pool configuration and update .env file.
     */
    private function getUserPoolConfigUpdEnv(): int
    {
        try {
            // Get user pool configuration from AWS Cognito
            $userPool = $this->getUserPoolConfig($this->userPoolId);

            $passwordPolicy = $userPool['Policies']['PasswordPolicy'] ?? [];
            if (!empty($passwordPolicy)) {
                // Set the value in .env file (Password Policy - Base 64 encoded data)
                $this->setEnv('AWS_COGNITO_PASSWORD_POLICY',
                    base64_encode(json_encode($passwordPolicy)));
            } // End if

            $signinPolicy = $userPool['Policies']['SignInPolicy'] ?? [];
            if (!empty($signinPolicy)) {
                // Set the value in .env file (Sign In Policy)
                $this->setEnv('AWS_COGNITO_SIGNIN_POLICY',
                    implode(',', $signinPolicy['AllowedFirstAuthFactors'] ?? []));
            } // End if

            // Set the value in .env file
            $this->setEnv('AWS_COGNITO_MFA_SETUP', $userPool['MfaConfiguration'] ?? 'OFF');

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('SyncCommand:getUserPoolConfigUpdEnv:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get User Pool Client configuration and update .env file.
     */
    private function getUserPoolClientConfigUpdEnv(): int
    {
        try {
            // Get user pool client configuration from AWS Cognito
            $userPoolClient = $this->getUserPoolClientConfig($this->userPoolId, $this->clientId);

            // Set the value in .env file
            $this->setEnv('AWS_COGNITO_CLIENT_SECRET', $userPoolClient['ClientSecret'] ?? '');

            // Check for ExplicitAuthFlows
            $explicitAuthFlows = $userPoolClient['ExplicitAuthFlows'] ?? [];
            if (!empty($explicitAuthFlows)) {
                //Set the value in .env file
                $this->setEnv('AWS_COGNITO_ALLOWED_AUTH_FLOWS', implode(',', $explicitAuthFlows));
            } // End if

            // Check for AuthFlowTypes
            $allowPasskeys = (in_array('ALLOW_' . Enums\CognitoAuthFlowTypes::USER_AUTH->value, $explicitAuthFlows));

            //Set the value in .env file
            $this->setEnv('AWS_COGNITO_ALLOW_PASSKEYS', $allowPasskeys ? true : false);

            $accessTokenValidity = $userPoolClient['AccessTokenValidity'] ?? 60; // Default to 60 minutes if not set
            $multiplyFactor = $userPoolClient['TokenValidityUnits']['AccessToken'] ?? 'minutes'; // Default to minutes if not set
            $accessTokenValidity *= ($multiplyFactor === 'hours' ? 60 : 1); // Convert hours to minutes
            $accessTokenValidity *= ($multiplyFactor === 'days' ? 1440 : 1); // Convert days to minutes

            //Set the value in .env file
            $this->setEnv('SESSION_LIFETIME', $accessTokenValidity);
            $this->setEnv('AUTH_PASSWORD_TIMEOUT', $accessTokenValidity*60); // Convert minutes to seconds

            // Set the value in .env file for token revocation
            $enableTokenRevocation = $userPoolClient['EnableTokenRevocation'] ?? true; // Default to true if not set
            $this->setEnv('AWS_COGNITO_ENABLE_TOKEN_REVOCATION', $enableTokenRevocation ? true : false);

            // Set the value in .env file for Auth Session Validity
            $authSessionValidity = $userPoolClient['AuthSessionValidity'] ?? 3;
            $this->setEnv('AWS_COGNITO_AUTH_SESSION_VALIDITY', ($authSessionValidity * 60)); // Convert minutes to seconds

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('SyncCommand:getUserPoolClientConfigUpdEnv:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get User Pool MFA configuration and update .env file.
     */
    private function getUserPoolMfaConfigUpdEnv(): int
    {
        try {
            // Get user pool MFA configuration from AWS Cognito
            $userPoolMfaConfig = $this->getUserPoolMfaConfig($this->userPoolId);

            // Check Software Token MFA configuration
            static $softwareTokenText = 'SOFTWARE_TOKEN_MFA';
            $softwareTokenEnabled = $userPoolMfaConfig['SoftwareTokenMfaConfiguration']['Enabled'] ?? false;
            $mfatypes = config('cognito.mfa_type');

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

            // Check WebAuthn MFA configuration
            $this->processUserPoolMfaWebAuthnConfig($userPoolMfaConfig);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('SyncCommand:getUserPoolMfaConfigUpdEnv:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Process User Pool MFA WebAuthn configuration and update .env file.
     *
     * @param array $userPoolMfaConfig
     */
    private function processUserPoolMfaWebAuthnConfig($userPoolMfaConfig): void
    {
        // Check WebAuthn MFA configuration
        $webauthnConfig = $userPoolMfaConfig['WebAuthnConfiguration'] ?? null;
        if ($webauthnConfig) {
            // Set RelyingPartyId if it differs from the current config value
            if ($webauthnConfig['RelyingPartyId'] !== config('cognito.web_authn_mfa_configuration.RelyingPartyId')) {
                $this->setEnv('AWS_COGNITO_WEB_AUTHN_RELYING_PARTY_ID', $webauthnConfig['RelyingPartyId']);
            } // End if

            // Set UserVerification if it differs from the current config value
            if ($webauthnConfig['UserVerification'] !== config('cognito.web_authn_mfa_configuration.UserVerificationMethod')) {
                $this->setEnv('AWS_COGNITO_WEB_AUTHN_USER_VERIFICATION_METHOD', $webauthnConfig['UserVerification']);
            } // End if

            // Set FactorConfiguration if it differs from the current config value
            if ($webauthnConfig['FactorConfiguration'] !== config('cognito.web_authn_mfa_configuration.FactorConfiguration')) {
                $this->setEnv('AWS_COGNITO_WEB_AUTHN_FACTOR_CONFIGURATION', $webauthnConfig['FactorConfiguration']);
            } // End if
        } // End if
    } //Function ends

    /**
     * Sync configuration from local .env file to AWS.
     *
     * @return int
     */
    private function syncLocalToAws(): int
    {
        // Initialize return value
        $returnValue = Command::SUCCESS;

        try {
            $this->newLine();
            $this->info('Syncing local .env configuration to AWS Cognito...');

            if ($this->userPoolId == 'wrong') {
                throw new Exception('User Pool ID is not set.');
            } // End if

            $this->info(self::DONE);
            $this->newLine();

            $returnValue = Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('SyncCommand:syncLocalToAws:Exception');
            throw $exception;
        } // Try-catch ends

        return $returnValue;
    } //Function ends

} // Class ends
