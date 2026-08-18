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
    protected $signature = 'cognito:purge {--pool : Create a new user pool}
                                {--client : Create a new user pool client}
                                {--terms : Create a new user pool terms}
                                {pool_id? : The user pool ID}
                                {client_id? : The user pool client ID}';

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
            $needles = ['pool', 'client', 'terms', 'groups'];
            $haystack = array_filter($this->options());
            if (empty(array_intersect($needles, array_keys($haystack)))) {
                throw new ConsoleException('Provide at least one option: --pool, --client, --terms, or --groups');
            } //End if

            // Get the user pool ID from the argument or from the .env file
            $this->userPoolId = $this->argument('pool_id') ?: null;

            // Get the user pool client ID from the argument or from the .env file
            $this->clientId = $this->argument('client_id') ?: null;

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
        $response = $this->deleteUserPool($this->userPoolId);

        // Success message
        $this->newLine();
        $this->info('✓ Successfully deleted user pool [' . $this->userPoolId . ']');

        return $response ? Command::SUCCESS : Command::FAILURE;
    } //Function ends

    /**
     * Prompt the user to purge a user pool client if it exists.
     *
     * @return int
     */
    private function promptUserToPurgeUserPoolClient(): int
    {
        $response = $this->deleteUserPoolClient($this->clientId, $this->userPoolId);

        // Success message
        $this->newLine();
        $this->info('✓ Successfully deleted user pool client [' . $this->clientId . ']');

        return $response ? Command::SUCCESS : Command::FAILURE;
    } //Function ends

} // Class ends
