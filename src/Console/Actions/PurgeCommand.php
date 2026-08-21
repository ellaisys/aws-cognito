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

use Exception;
use Ellaisys\Cognito\Exceptions\ConsoleException;

class PurgeCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:purge {--pool : Purge the user pool}
                                {--client : Purge the user pool client}
                                {--terms : Purge the user pool terms}
                                {--pool-id= : The user pool ID}
                                {--client-id= : The user pool client ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge a resource in AWS Cognito (user pool, user pool client, terms, or groups)';

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
            // Confirm the action
            if (! $this->confirm('Would you like to purge the AWS Cognito resource now?', true))
            {
                $this->components->warn('AWS Cognito purge cancelled.');
                $this->components->info('You can run the purge later using "php artisan cognito:purge".');
                return $returnValue;
            } // End if

            //Check if at least one option is provided
            $needles = ['pool', 'client', 'terms'];
            $haystack = array_filter($this->options());
            if (empty(array_intersect($needles, array_keys($haystack)))) {
                throw new ConsoleException('Provide at least one option: --pool, --client, or --terms');
            } //End if

            // Get the user pool ID from the argument or from the .env file
            $this->userPoolId = $this->option('pool-id') ?: null;

            // Get the user pool client ID from the argument or from the .env file
            $this->clientId = $this->option('client-id') ?: null;

            $this->newLine();
            $this->info('Starting resource purge...');

            // Create user pool
            if ($this->option('pool')) {
                $returnValue = $this->promptUserToPurgeUserPool();
            } //End if

            // Create user pool client
            if ($this->option('client')) {
                $returnValue = $this->promptUserToPurgeUserPoolClient();
            } //End if

            $this->newLine();
            $this->info('✓ Successfully purged the resource.');
        } catch (Exception $exception) {
            Log::error('PurgeCommand:handle:Exception');
            $this->components->error($exception->getMessage());
            $returnValue = Command::FAILURE;
        } // Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Prompt the user to purge a user pool if it exists.
     *
     * @return int
     */
    private function promptUserToPurgeUserPool(): int
    {
        if (empty($this->userPoolId)) {
            $userPool = $this->promptUserForUserPoolId();
            $this->userPoolId = $userPool['id'];
        } // End if
        $response = $this->deleteUserPool($this->userPoolId);

        // Success message
        $this->newLine();
        $this->info('✓ Successfully deleted user pool [' . $this->userPoolId . ']');

        return $response ? Command::SUCCESS : Command::FAILURE;
    } //Function ends

    /**
     * Prompt the user to select a user pool to be purged
     *
     * @return array{id: string, name: string, status: string}
     */
    private function promptUserForUserPoolId(): array
    {
        try {
            // Get the list of user pools
            $userPools = $this->getUserPools();
            if (empty($userPools)) {
                throw new ConsoleException('No user pools found in AWS Cognito.');
            } // End if

            // Initialize the data map for user pools
            $poolMap = [];
            $choice = null;            

            foreach ($userPools as $pool) {
                $label = "{$pool['Name']} [{$pool['Id']}]";
                $poolMap[$label] = [
                    'id' => $pool['Id'],
                    'name' => $pool['Name'],
                    'status' => 'existing'
                ];
            } // End foreach

            // Prompt the user to select a pool or create a new one
            $choice = $this->choice(
                'Select the pool:',
                [...array_keys($poolMap)],
                0 // Default choice index
            );

            // Validate the selected choice
            if (!isset($poolMap[$choice])) {
                throw new ConsoleException('Invalid selection of user pool.');
            } // End if

            // Return the selected pool's ID and name
            return $poolMap[$choice];
        } catch (Exception $exception) {
            Log::error('InstallCommand:promptUserForUserPoolId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Prompt the user to purge a user pool client if it exists.
     *
     * @return int
     */
    private function promptUserToPurgeUserPoolClient(): int
    {
        if (empty($this->userPoolId)) {
            throw new ConsoleException('User pool ID is required to purge a user pool client. Please provide the user pool ID using the --pool_id option or as an argument.');
        } // End if

        if (empty($this->clientId)) {
            $client = $this->promptUserForUserPoolClientId();
            $this->clientId = $client['id'];
        } // End if

        $response = $this->deleteUserPoolClient($this->clientId, $this->userPoolId);

        // Success message
        $this->newLine();
        $this->info('✓ Successfully deleted user pool client [' . $this->clientId . ']');

        return $response ? Command::SUCCESS : Command::FAILURE;
    } //Function ends

    /**
     * Prompt the user to select a user pool client or create a new one.
     *
     * @return array{id: string, name: string}
     */
    private function promptUserForUserPoolClientId(): array
    {
        try {
            // Get the list of user pools
            $clients = $this->getUserPoolClients($this->userPoolId);
            if (empty($clients)) {
                throw new ConsoleException('No user pool clients found in AWS Cognito for the specified user pool.');
            } // End if

            // Initialize the data map for user pool clients
            $dataMap = [];
            $choice = null;

            foreach ($clients as $client) {
                $label = "{$client['ClientName']} [{$client['ClientId']}]";
                $dataMap[$label] = [
                    'id' => $client['ClientId'],
                    'name' => $client['ClientName'],
                ];
            } // End foreach

            // Prompt the user to select a client or create a new one
            $choice = $this->choice(
                'Select the user pool client:',
                [...array_keys($dataMap)],
                0, // Default choice index
            );

            // Validate the selected choice
            if (!isset($dataMap[$choice])) {
                throw new ConsoleException('Invalid selection.');
            } // End if

            // Return the selected client's ID
            return $dataMap[$choice];
        } catch (Exception $exception) {
            Log::error('InstallCommand:getUserPoolClientId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

} // Class ends
