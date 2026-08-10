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

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Console\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Console\Traits\UtilsTrait;

use Exception;

class InstallCommand extends Command
{
    use AwsCognitoTrait;
    use UtilsTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install AWS Cognito Auth solution.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Check for AWS connectivity
            if ($this->checkHygieneData() === Command::FAILURE) {
                return Command::FAILURE;
            } // End if

            // Set environment variables
            if ($this->setEnvironment() === Command::FAILURE) {
                return Command::FAILURE;
            } // End if

            // Get the user pool
            if ($this->getUserPoolId() === Command::FAILURE) {
                return Command::FAILURE;
            } // End if

            return self::SUCCESS;
        } catch (Exception $exception) {
            Log::error('InstallCommand:handle:Exception');
            $this->error($exception->getMessage());
            return Command::FAILURE;
        } // Try-catch ends
        return Command::SUCCESS;
    } //Function ends

    /**
     * Check for AWS connectivity and essential environment variables.
     *
     * @return int
     */
    private function checkHygieneData(): int
    {
        try {
            $this->info('Checking AWS configurations ...');
            $bar = $this->output->createProgressBar(4);
            $bar->start();

            env('AWS_ACCESS_KEY_ID') ?: $this->error('AWS_ACCESS_KEY_ID is not set in .env file.');
            $bar->advance();

            env('AWS_SECRET_ACCESS_KEY') ?: $this->error('AWS_SECRET_ACCESS_KEY is not set in .env file.');
            $bar->advance();

            env('AWS_DEFAULT_REGION') ?: $this->error('AWS_DEFAULT_REGION is not set in .env file.');
            $bar->advance();

            // Check if AWS_COGNITO_REGION is set and matches AWS_DEFAULT_REGION
            if (empty(env('AWS_COGNITO_REGION')) ||
                (env('AWS_DEFAULT_REGION') !== env('AWS_COGNITO_REGION'))) {
                $this->setEnv('AWS_COGNITO_REGION', env('AWS_DEFAULT_REGION'));
            } // End if

            // Check AWS connectivity
            $response = $this->getUserPools();
            $bar->advance();

            $bar->finish();

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('InstallCommand:checkHygieneData:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Set environment variables for AWS Cognito.
     *
     * @return int
     */
    private function setEnvironment(): int
    {
        try {

            $prefixWeb = $this->ask('What is the prefix for your web routes? e.g. /cognito/login', 'cognito');
            $prefixApi = $this->ask('What is the prefix for your API routes? e.g. /api/cognito/login', 'cognito');

            $this->info('Setting environment variables ...');
            $bar = $this->output->createProgressBar(6);
            $bar->start();

            // Set AWS_COGNITO_VERSION to latest
            $this->setEnv('AWS_COGNITO_VERSION', 'latest');
            $bar->advance();

            // Set AWS_COGNITO_ADD_USER_DELIVERY_MEDIUMS to EMAIL
            $this->setEnv('AWS_COGNITO_ADD_USER_DELIVERY_MEDIUMS', 'EMAIL');
            $bar->advance();

            // Set AWS_COGNITO_TOKEN_STORAGE to file
            $this->setEnv('AWS_COGNITO_TOKEN_STORAGE', 'file');
            $bar->advance();

            // Set AWS_COGNITO_FORCE_NEW_USER_PASSWORD to false
            $this->setEnv('AWS_COGNITO_FORCE_NEW_USER_PASSWORD', false);
            $bar->advance();

            // Set AWS_COGNITO_ALLOW_PHONE_NUMBER to false
            $this->setEnv('AWS_COGNITO_ALLOW_PHONE_NUMBER', false);
            $bar->advance();

            // Set AWS_COGNITO_WEB_PREFIX and AWS_COGNITO_API_PREFIX
            $this->setEnv('AWS_COGNITO_WEB_PREFIX', $prefixWeb);
            $this->setEnv('AWS_COGNITO_API_PREFIX', $prefixApi);
            $bar->advance();

            $bar->finish();

            // Display the set environment variables
            $this->newLine();
            $this->info("Environment set successfully. To make changes, edit the .env file and run 'php artisan config:clear'.");

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('InstallCommand:setEnvironment:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get selected user pool ID. Create a new user pool if the user chooses
     * to do so.
     *
     * @return int
     */
    private function getUserPoolId(): int
    {
        // Initialize variables
        $returnValue = null;
        try {
            // If the user pool ID is already set in the .env file, return it
            $userPool = [];
            if (!empty(env('AWS_COGNITO_USER_POOL_ID'))) {
                $userPool = [
                    'id' => env('AWS_COGNITO_USER_POOL_ID'),
                    'name' => 'existing',
                    'status' => 'existing'
                ];
            } else {
                // Get the user pool ID from the user
                $userPool = $this->promptUserForUserPoolId();
            } // End if-else

            // Validate the user pool ID
            if (empty($userPool['id'])) {
                throw new Exception('User pool ID is not set. Please check your AWS configuration and retry.');
            } // End if

            // Check the client ID and secret in the .env file
            $clientId = $this->getUserPoolClientId($userPool['id'], $userPool['status'] === 'new');

            // Sync the user pool configuration to the .env file
            $this->call('cognito:sync-config', [
                'pool_id' => $userPool['id'],
                'client_id' => $clientId,
                '--down' => true
            ]);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('InstallCommand:getUserPoolId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Prompt the user to select a user pool or create a new one.
     *
     * @return array{id: string, name: string, status: string}
     */
    private function promptUserForUserPoolId(): array
    {
        try {
            // Get the list of user pools
            $userPools = $this->getUserPools();

            $poolMap = [];
            foreach ($userPools as $pool) {
                $label = "{$pool['Name']} [{$pool['Id']}]";
                $poolMap[$label] = [
                    'id' => $pool['Id'],
                    'name' => $pool['Name'],
                    'status' => 'existing'
                ];
            } // End foreach

            $createNew = 'Create New Resource';

            // Prompt the user to select a pool or create a new one
            $choice = $this->choice(
                'Select the pool:',
                [...array_keys($poolMap), $createNew]
            );

            /**
             * If the user chooses to create a new resource, prompt for the name.
             * Create a new pool with the provided name and return its ID and name.
             * Otherwise, return the selected pool's ID and name.
             */
            if ($choice === $createNew) {
                $name = $this->ask('Enter the name of the new pool:');
                $newPool = $this->createUserPool($name);

                $poolMap[$choice] = [
                    'id' => $newPool['Id'],
                    'name' => $name,
                    'status' => 'new'
                ];
            } // End if

            // Validate the selected choice
            if (!isset($poolMap[$choice])) {
                $this->error('Invalid selection. Please try again.');
                throw new Exception('Invalid selection.');
            } // End if

            // Set the selected pool ID in the .env file
            $this->setEnv('AWS_COGNITO_USER_POOL_ID', $poolMap[$choice]['id']);

            // Return the selected pool's ID and name
            return $poolMap[$choice];
        } catch (Exception $exception) {
            Log::error('InstallCommand:promptUserForUserPoolId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Get selected user pool ID. Create a new user pool if the user chooses
     * to do so.
     *
     * @return string|null
     */
    private function getUserPoolClientId(?string $userPoolId, bool $isNew = false): ?string
    {
        // Initialize variables
        $returnValue = null;

        try {
            // If the client ID is already set in the .env file, return it
            $client = [];
            // If the user pool client ID is already set in the .env file, return it
            if (!empty(env('AWS_COGNITO_CLIENT_ID')) &&
                !empty(env('AWS_COGNITO_CLIENT_SECRET')) && !$isNew) {
                $client = [
                    'id' => env('AWS_COGNITO_CLIENT_ID'),
                    'name' => 'existing',
                    'status' => 'existing',
                    'secret' => env('AWS_COGNITO_CLIENT_SECRET')
                ];
            } else {
                // Get the user pool client ID from the user
                $client = $this->promptUserForUserPoolClientId($userPoolId, $isNew);
            } // End if-else

            // Return the selected client's ID
            return $client['id'];
        } catch (Exception $exception) {
            Log::error('InstallCommand:getUserPoolClientId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Prompt the user to select a user pool client or create a new one.
     *
     * @return array{id: string, name: string}
     */
    private function promptUserForUserPoolClientId(?string $userPoolId, bool $isNew = false): array
    {
        // Initialize variables
        $returnValue = null;

        try {
            // Get the list of user pools
            $clients = $this->getUserPoolClients($userPoolId);

            $dataMap = [];
            foreach ($clients as $client) {
                $label = "{$client['ClientName']} [{$client['ClientId']}]";
                $dataMap[$label] = [
                    'id' => $client['ClientId'],
                    'name' => $client['ClientName'],
                ];
            } // End foreach

            $createNew = 'Create New Resource';

            // Prompt the user to select a client or create a new one
            $choice = $this->choice(
                'Select the user pool client:',
                [...array_keys($dataMap), $createNew]
            );

            /**
             * If the user chooses to create a new resource, prompt for the name.
             * Create a new client with the provided name and return its ID and secret.
             * Otherwise, return the selected client's ID and secret.
             */
            if ($choice === $createNew) {
                $name = $this->ask('Enter the name of the new user pool client:');
                $newPool = $this->createUserPoolClient($name);

                $dataMap[$choice] = [
                    'id' => $newPool['Id'],
                    'name' => $name
                ];
            } // End if

            // Validate the selected choice
            if (!isset($dataMap[$choice])) {
                $this->error('Invalid selection. Please try again.');
                throw new Exception('Invalid selection.');
            } // End if

            // Set the selected client ID in the .env file
            $this->setEnv('AWS_COGNITO_CLIENT_ID', $dataMap[$choice]['id']);

            
            $this->setEnv('AWS_COGNITO_CLIENT_SECRET', $dataMap[$choice]['name']);

            // Return the selected client's ID
            return $dataMap[$choice];
        } catch (Exception $exception) {
            Log::error('InstallCommand:getUserPoolClientId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

} // Class ends
