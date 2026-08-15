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
use Ellaisys\Cognito\Exceptions\ConsoleException;

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
     * The user pool ID.
     *
     * @var string|null
     */
    private ?string $userPoolId = null;


    /**
     * The user pool Name.
     *
     * @var string|null
     */
    private ?string $userPoolName = null;

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
            // Display the header
            $this->displayHeader();

            // Confirm installation
            if (! $this->confirm('Would you like to configure AWS Cognito now?', true))
            {
                $this->components->warn('AWS Cognito installation cancelled.');
                $this->components->info('You can run the installer later using "php artisan cognito:install".');
                return $returnValue;
            } // End if

            // Confirm AWS credentials
            if (! $this->confirm('Are your AWS credentials configured and ready to use?', true))
            {
                throw new ConsoleException('AWS credentials are not configured. Please configure your AWS credentials before running the installer.');
            } // End if

            // Check for AWS connectivity
            if ($this->checkHygieneData() === Command::FAILURE) {
                throw new ConsoleException('Unable to verify the AWS configuration. Please check your AWS credentials and configuration, then try again.');
            } // End if

            // Prompt for database migration
            if ($this->promptUserForDatabaseMigration() === Command::FAILURE) {
                throw new ConsoleException('Database migration failed. Please check your database configuration and retry.');
            } // End if

            // Set environment variables
            if ($this->setEnvironment() === Command::FAILURE) {
                throw new ConsoleException('Unable to set environment variables. Please check your .env file and retry.');
            } // End if

            // Get the user pool
            if ($this->getUserPoolId() === Command::FAILURE) {
                throw new ConsoleException('Could not set up the User Pool. Please check your AWS Cognito configuration and retry.');
            } // End if

            // Prompt for user groups
            if ($this->confirm('Would you like to assign new users to a default group?', false) &&
                ($this->promptUserForUserGroups() === Command::FAILURE)) {
                throw new ConsoleException('Default user group configuration could not be completed.');
            } // End if

            // Display success message
            $this->newLine();
            $this->info('✓ AWS Cognito has been configured successfully.');
            $this->newLine();
        } catch (Exception $exception) {
            Log::error('InstallCommand:handle:Exception');
            $this->components->error($exception->getMessage());
            return Command::FAILURE;
        } // Try-catch ends
        return $returnValue;
    } //Function ends

    /**
     * Check for AWS connectivity and essential environment variables.
     *
     * @return int
     */
    private function checkHygieneData(): int
    {
        try {
            $this->info('Checking AWS configurations...');
            $bar = $this->output->createProgressBar(4);
            $bar->start();

            if (empty(env('AWS_ACCESS_KEY_ID'))) {
                throw new ConsoleException('AWS_ACCESS_KEY_ID is not set in .env file.');
            }
            $bar->advance();

            if (empty(env('AWS_SECRET_ACCESS_KEY'))) {
                throw new ConsoleException('AWS_SECRET_ACCESS_KEY is not set in .env file.');
            }
            $bar->advance();

            if (empty(env('AWS_DEFAULT_REGION'))) {
                throw new ConsoleException('AWS_DEFAULT_REGION is not set in .env file.');
            }
            $bar->advance();

            // Check if AWS_COGNITO_REGION is set and matches AWS_DEFAULT_REGION
            if (empty(env('AWS_COGNITO_REGION')) ||
                (env('AWS_DEFAULT_REGION') !== env('AWS_COGNITO_REGION'))) {
                $this->setEnv('AWS_COGNITO_REGION', env('AWS_DEFAULT_REGION'));
            } // End if

            // Check AWS connectivity
            $this->getUserPools();
            $bar->advance();
            $bar->finish();

            // Display success message
            $this->newLine();
            $this->info('✓ AWS connectivity is established');
            $this->newLine();

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
            // Prompt the user for web and API route prefixes
            $prefixWeb = $this->ask( 'Enter the web route prefix (e.g. cognito)', 'cognito' );
            $prefixApi = $this->ask( 'Enter the API route prefix (e.g. api/cognito)', 'cognito' );

            $this->info('Setting environment variables...');
            $bar = $this->output->createProgressBar(7);
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

            // Set AWS_COGNITO_WEB_PREFIX
            $this->setEnv('AWS_COGNITO_WEB_PREFIX', $prefixWeb);
            $bar->advance();

            // Set AWS_COGNITO_API_PREFIX
            $this->setEnv('AWS_COGNITO_API_PREFIX', $prefixApi);
            $bar->advance();
            $bar->finish();

            // Display the set environment variables
            $this->newLine();
            $this->info('✓ Environment configured');
            $this->comment("To make changes, edit the .env file and run 'php artisan config:clear'.");
            $this->newLine();

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
        try {
            // If the user pool ID is already set in the .env file, return it
            $userPool = [];
            if (!empty(env('AWS_COGNITO_USER_POOL_ID'))) {
                $userPool = [
                    'id' => env('AWS_COGNITO_USER_POOL_ID'),
                    'name' => 'existing',
                    'status' => 'existing'
                ];

                // Display success message
                $this->newLine();
                $this->info('✓ Cognito user pool configured.');
            } else {
                // Get the user pool ID from the user
                $userPool = $this->promptUserForUserPoolId();
            } // End if-else

            // Validate the user pool ID
            if (empty($userPool['id'])) {
                throw new ConsoleException('User pool ID is not set. Please check your AWS configuration and retry.');
            } // End if
            $this->userPoolId = $userPool['id'];
            $this->userPoolName = $userPool['name'];

            // Check the client ID and secret in the .env file
            $this->clientId = $this->getUserPoolClientId($this->userPoolId, $userPool['status'] === 'new');

            // Sync the user pool configuration to the .env file
            $this->callSilently('cognito:sync-config', [
                'pool_id' => $this->userPoolId,
                'client_id' => $this->clientId,
                '--aws-to-local' => true
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
            // Initialize the data map for user pools
            $poolMap = [];
            $choice = $createNew = 'Create New User Pool';

            // Get the list of user pools
            $userPools = $this->getUserPools();

            if (!empty($userPools)) {
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
                    [...array_keys($poolMap), $createNew],
                    0 // Default choice index
                );
            } // End if

            /**
             * If the user chooses to create a new resource, prompt for the name.
             * Create a new pool with the provided name and return its ID and name.
             * Otherwise, return the selected pool's ID and name.
             */
            if ($choice === $createNew) {
                $suggestedName = 'NewPool-' . Str::random(5);
                $name = $this->ask('Enter the name of the new pool', $suggestedName);

                // Create the new pool
                $newPool = $this->createUserPool($name);

                $poolMap[$choice] = [
                    'id' => $newPool['Id'],
                    'name' => $name,
                    'status' => 'new'
                ];
            } // End if

            // Validate the selected choice
            if (!isset($poolMap[$choice])) {
                throw new ConsoleException('Invalid selection of user pool.');
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

                // Display success message
                $this->newLine();
                $this->info('✓ Cognito user pool client configured.');
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
    private function promptUserForUserPoolClientId(?string $userPoolId,
        bool $isNew = false): array
    {
        try {
            // Initialize the data map for user pool clients
            $dataMap = [];
            $choice = $createNew = 'Create New User Pool Client';

            // If it's not a new user pool, get the list of existing clients
            if (!$isNew) {
                // Get the list of user pools
                $clients = $this->getUserPoolClients($userPoolId);

                foreach ($clients as $client) {
                    $label = "{$client['ClientName']} [{$client['ClientId']}]";
                    $dataMap[$label] = [
                        'id' => $client['ClientId'],
                        'name' => $client['ClientName'],
                    ];
                } // End foreach

                $createNew = 'Create New User Pool Client';

                // Prompt the user to select a client or create a new one
                $choice = $this->choice(
                    'Select the user pool client:',
                    [...array_keys($dataMap), $createNew],
                    0, // Default choice index
                );
            } // End if

            /**
             * If the user chooses to create a new resource, prompt for the name.
             * Create a new client with the provided name and return its ID and secret.
             * Otherwise, return the selected client's ID and secret.
             */
            if ($choice === $createNew) {
                $suggestedName = $this->userPoolName ? $this->userPoolName . 'Client' : 'NewClient';
                $name = $this->ask('Enter the name of the new user pool client', $suggestedName);

                // Create the new client
                $newClient = $this->createUserPoolClient($name, $userPoolId);

                $dataMap[$choice] = [
                    'id' => $newClient['ClientId'],
                    'name' => $name
                ];
            } // End if

            // Validate the selected choice
            if (!isset($dataMap[$choice])) {
                throw new ConsoleException('Invalid selection.');
            } // End if

            // Set the selected client ID in the .env file
            $this->setEnv('AWS_COGNITO_CLIENT_ID', $dataMap[$choice]['id']);
            $this->setEnv('AWS_COGNITO_CLIENT_SECRET', "");

            // Return the selected client's ID
            return $dataMap[$choice];
        } catch (Exception $exception) {
            Log::error('InstallCommand:getUserPoolClientId:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Prompt the user to select a user pool group or create a new one.
     *
     * @return int
     */
    private function promptUserForUserGroups(): int
    {
        try {
            //Initialize the data map for user groups
            $dataMap = [];
            $choice = $createNew = 'Create New Resource';

            // Get the list of user groups in the selected user pool
            $results = $this->getUserPoolGroups($this->userPoolId);

            // Existing user groups are available
            if (!empty($results)) {
                foreach ($results as $group) {
                    $label = "{$group['GroupName']} [{$group['Description']}]";
                    $dataMap[$label] = [
                        'id' => $group['GroupName'],
                        'name' => $group['Description'],
                    ];
                } // End foreach

                // Prompt the user to select a resource or create a new one
                $choice = $this->choice(
                    'Select the user pool group:',
                    [...array_keys($dataMap), $createNew]
                );
            } // End if

            // Create a new resource
            if ($choice === $createNew) {
                $dataMap[$choice] = $this->promptUserForNewUserGroup();
            } // End if

            // Validate the selected choice
            if (!isset($dataMap[$choice])) {
                throw new ConsoleException('Invalid selection.');
            } // End if

            // Set the selected client ID in the .env file
            $this->setEnv('AWS_COGNITO_DEFAULT_USER_GROUP', $dataMap[$choice]['id']);

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('InstallCommand:promptUserForUserGroups:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    /**
     * Prompt the user to create a new user group.
     *
     * @return array{id: string, name: string}
     */
    private function promptUserForNewUserGroup(): array
    {
        try {
            // Prompt the user to create a new group
            if ($this->confirm('Do you want to create a new user group?', true)) {
                $groupName = $this->ask('Enter the name of the new group:', 'default');
                $groupDescription = $this->ask('Enter the description of the new group:', 'Default Group');

                // Create the new group
                $data = $this->callSilently('cognito:make', [
                    'name' => $groupName,
                    'description' => $groupDescription,
                    '--groups' => true
                ]);

                // Display success message
                $this->newLine();
                $this->info("✓ User group '{$groupName}' created successfully.");

                return [
                    'id' => $data['GroupName'] ?? $groupName,
                    'name' => $data['Description'] ?? $groupDescription,
                ];
            } // End if

            return [
                'id' => null,
                'name' => null,
            ];
        } catch (Exception $exception) {
            Log::error('InstallCommand:promptUserForNewUserGroup:Exception');
            throw $exception;
        } // Try-catch ends
    } //Function ends

    private function promptUserForDatabaseMigration(): int
    {
        $choices = [
            'Yes' => 'yes',
            'No' => 'no',
        ];

        $choice = $this->choice(
            'Do you want to run the database migration now?',
            array_keys($choices),
            1 // Default to 'No'
        );

        if ($choices[$choice] === 'yes') {
            $this->call('migrate');
            $this->info('✓ Database migration completed.');
        } else {
            $this->info('Database migration skipped. You can run it later using "php artisan migrate".');
        }

        return Command::SUCCESS;
    } //Function ends

    /**
     * Display the header for the installation process.
     */
    private function displayHeader(): void
    {
        $this->newLine();

        $this->line(' ┌───────────────────────────────────────────┐');
        $this->line(' │ AWS Cognito Package Installation          │');
        $this->line(' └───────────────────────────────────────────┘');

        $this->newLine();
    } //Function ends

} // Class ends
