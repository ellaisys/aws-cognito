<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Workbench\App\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Console\Traits\AwsCognitoTrait;

use Exception;
use Ellaisys\Cognito\Exceptions\ConsoleException;

class CleanSlateCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:clean-slate
                                {--pool-id= : The user pool ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean slate for AWS Cognito resources during phpunit tests';

    /**
     * The user pool ID.
     *
     * @var string|null
     */
    private ?string $userPoolId = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize return value
        $returnValue = Command::SUCCESS;

        try {
            // Confirm the action
            if (! $this->confirm('Clean slate?', true))
            {
                $this->components->warn('cancelled.');
                return $returnValue;
            } // End if

            // Get the user pool ID from the option or from the .env file
            $this->userPoolId = $this->option('pool-id') ?: null;

            $this->newLine();
            $this->info('Initiating clean slate...');

            // Clean all user pools from AWS Cognito, with name TempUserPool
            $this->pools();

            // Clean all clients from AWS Cognito, except WorkbenchPoolClient
            $this->clients();

            // Clean all groups from AWS Cognito, excluding the default group
            $this->groups();

            // Clean all terms from AWS Cognito
            $this->terms();

            $this->newLine();
            $this->info('✓ Successfully cleaned slate.');
        } catch (Exception $exception) {
            Log::error('CleanSlateCommand:handle:Exception');
            $this->components->error($exception->getMessage());
            $returnValue = Command::FAILURE;
        } // Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Cleans all user pools from AWS Cognito, with name TempUserPool.
     */
    private function pools(): void
    {
        try {
            $client = app()->make(AwsCognitoClient::class);

            // Fetch the list of user pools from AWS Cognito
            $response = $client->listUserPools();
            $pools = $response['UserPools'] ?? [];

            // Iterate through each pool and delete it
            foreach ($pools as $pool) {
                if ($pool['Name'] === 'TempUserPool') {
                    // Delete the user pool from AWS Cognito
                    $client->deleteUserPool($pool['Id']);
                } //End if
            } //Loop ends
        } catch (Exception $exception) {
            Log::error("AwsCleanSlate:pools:Exception");
        } //Try-catch ends
    } //Function ends

    /**
     * Cleans all clients from AWS Cognito, except WorkbenchPoolClient.
     */
    private function clients(): void
    {
        try {
            $client = app()->make(AwsCognitoClient::class);

            // Fetch the list of clients from AWS Cognito
            $response = $client->listUserPoolClients(10, null, $this->userPoolId);
            $clients = $response['UserPoolClients'] ?? [];

            // Iterate through each group and delete it
            foreach ($clients as $clientItem) {
                if ($clientItem['ClientName'] !== 'WorkbenchPoolClient') {
                    // Delete the client from AWS Cognito
                    $client->deleteUserPoolClient($this->userPoolId, $clientItem['ClientId']);
                } //End if
            } //Loop ends
        } catch (Exception $exception) {
            Log::error("AwsCleanSlate:clients:Exception");
        } //Try-catch ends
    } //Function ends

    /**
     * Cleans all users from AWS Cognito with email starting with
     * 'ellaisys+tb_tmp_*'
     */
    private function users(): void
    {
        try {
            $client = app()->make(AwsCognitoClient::class);

            // Fetch the list of users from AWS Cognito
            $response = $client->listUsers(10, null, $this->userPoolId);
            $users = $response['Users'] ?? [];

            // Iterate through each user and delete it if email matches the pattern
            foreach ($users as $user) {
                $email = $user['Attributes']['email'] ?? '';
                if (str_starts_with($email, 'ellaisys+tb_tmp_')) {
                    $client->deleteUser($this->userPoolId, $user['Username']);
                } //End if
            } //Loop ends
        } catch (Exception $exception) {
            Log::error("AwsCleanSlate:users:Exception");
        } //Try-catch ends
    } //Function ends

    /**
     * Cleans all groups from AWS Cognito, excluding the default group.
     */
    private function groups(): void
    {
        try {
            $client = app()->make(AwsCognitoClient::class);

            // Fetch the list of groups from AWS Cognito
            $response = $client->listGroups(10, null, $this->userPoolId);
            $groups = $response['Groups'] ?? [];

            // Iterate through each group and delete it
            foreach ($groups as $group) {
                if ($group['GroupName'] !== 'default') {
                    $client->deleteGroup($group['GroupName'], $this->userPoolId);
                } //End if
            } //Loop ends
        } catch (Exception $exception) {
            Log::error("AwsCleanSlate:groups:Exception");
        } //Try-catch ends
    } //Function ends

    /**
     * Cleans all terms from AWS Cognito.
     */
    private function terms(): void
    {
        try {
            $client = app()->make(AwsCognitoClient::class);

            // Fetch the list of terms from AWS Cognito
            $response = $client->listTerms(10, null, $this->userPoolId);
            $terms = $response['Terms'] ?? [];

            // Iterate through each term and delete it
            foreach ($terms as $term) {
                $client->deleteTerms($term['TermsId'], $this->userPoolId);
            } //Loop ends
        } catch (Exception $exception) {
            Log::error("AwsCleanSlate:terms:Exception");
        } //Try-catch ends
    } //Function ends

} // Class ends
