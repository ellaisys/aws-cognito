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

class MakeCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:make {--pool : Create a new user pool}
                                {--client : Create a new user pool client}
                                {--terms : Create a new user pool terms}
                                {--groups : Create a new user pool groups}
                                {name : Provide a name for the resource to be created}
                                {description? : Provide a description for the resource to be created}
                                {pool_id? : The user pool ID}
                                {client_id? : The user pool client ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource in AWS Cognito (user pool, user pool client, terms, or groups)';

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
        try {
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

            // Name argument is provided
            if (empty($this->argument('name'))) {
                throw new ConsoleException('Provide a name for the resource to be created.');
            } //End if
            $resourceName = $this->argument('name');

            // Description argument is optional
            $resourceDescription = $this->argument('description') ?: 'Default Description';

            $this->newLine();
            $this->info('Starting resource creation...');

            $returnValue = [];

            // Create user pool
            if ($this->option('pool')) {
                $returnValue['option'] = 'pool';
                $returnValue['message'] = 'Created new user pool.';
                $returnValue['data'] = $this->createUserPool(Str::studly($resourceName));
            } //End if

            // Create user pool client
            if ($this->option('client')) {
                $returnValue['option'] = 'client';
                $returnValue['message'] = 'Created new user pool client.';
                $returnValue['data'] = $this->createUserPoolClient($resourceName);
            } //End if

            // Create user pool terms
            if ($this->option('terms')) {
                $returnValue['option'] = 'terms';
                $returnValue['message'] = 'Created new user pool terms.';
                $returnValue['data'] = $this->createUserPoolTerms($resourceName);
            } //End if

            // Create user group
            if ($this->option('groups')) {
                $returnValue['option'] = 'groups';
                $returnValue['message'] = 'Created new user pool group.';
                $returnValue['data'] = $this->createUserPoolGroup(
                    Str::camel($resourceName), $resourceDescription,
                    $this->userPoolId
                );
            } //End if

            $this->newLine();
            $this->info('Created resource successfully.');

            $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('MakeCommand:handle:Exception');
            $this->components->error($exception->getMessage());
            return Command::FAILURE;
        } // Try-catch ends
        return Command::SUCCESS;
    } //Function ends

} // Class ends
