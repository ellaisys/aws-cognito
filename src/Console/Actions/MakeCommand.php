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

class MakeCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:make {--pool : Get list of user pools}
                                {--client : Get list of user pool clients}
                                {--terms : Get list of user pool terms}
                                {--groups : Get list of user pool groups}
                                {name : Provide a name for the resource to be created}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new resource in AWS Cognito (user pool, user pool client, terms, or groups)';

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
                $this->error('Please provide at least one option: --pool, --client, --terms, or --groups');
                return Command::FAILURE;
            } //End if

            // Name argument is provided
            if ($this->argument('name') === null) {
                $this->error('Please provide a name for the resource to be created.');
                return Command::FAILURE;
            } //End if
            $resourceName = Str::studly($this->argument('name'));

            $returnValue = [];

            $this->info('Fetching data...');

            if ($this->option('pool')) {
                $returnValue['option'] = 'pool';
                $returnValue['message'] = 'Created new user pool.';
                $returnValue['data'] = $this->createUserPool($resourceName);
            }

            if ($this->option('client')) {
                $returnValue['option'] = 'client';
                $returnValue['message'] = 'User pool clients list.';
                $returnValue['data'] = $this->getUserPoolClients();
            }

            if ($this->option('terms')) {
                $returnValue['option'] = 'terms';
                $returnValue['message'] = 'User pool terms list.';
                $returnValue['data'] = $this->getUserPoolTerms();
            }

            if ($this->option('groups')) {
                $returnValue['option'] = 'groups';
                $returnValue['message'] = 'User pool groups list.';
                $returnValue['data'] = $this->getUserPoolGroups();
            }

            $this->info($returnValue['message'] ?? 'Created resource successfully.');
            $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            Log::error('MakeCommand:handle:Exception');
            $this->error('Error retrieving configuration data.' . $exception->getMessage());
            return Command::FAILURE;
        } // Try-catch ends
        return Command::SUCCESS;
    } //Function ends

} // Class ends
