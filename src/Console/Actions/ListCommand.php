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
use Ellaisys\Cognito\Console\Traits\AwsCognitoTrait;

use Exception;
use Ellaisys\Cognito\Exceptions\ConsoleException;

class ListCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:list {--pool : Get list of user pools}
                                {--client : Get list of user pool clients}
                                {--term : Get list of user pool terms}
                                {--group : Get list of user pool groups}
                                {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List of resources in AWS Cognito (user pools, user pool clients, terms, or groups)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Initialize return value
        $returnValue = Command::SUCCESS;

        try {
            //Check if at least one option is provided
            $needles = ['pool', 'client', 'term', 'group'];
            $haystack = array_filter($this->options());
            if (empty(array_intersect($needles, array_keys($haystack)))) {
                throw new ConsoleException('Provide at least one option: --pool, --client, --term, or --group');
            } //End if

            // Display a message indicating that data is being fetched
            $this->newLine();
            $this->info('Fetching data...');

            $returnValue = [];
            if ($this->option('pool')) {
                $returnValue['option'] = 'pool';
                $returnValue['message'] = 'User pools list.';
                $returnValue['data'] = $this->getUserPools();
            }

            if ($this->option('client')) {
                $returnValue['option'] = 'client';
                $returnValue['message'] = 'User pool clients list.';
                $returnValue['data'] = $this->getUserPoolClients();
            }

            if ($this->option('term')) {
                $returnValue['option'] = 'term';
                $returnValue['message'] = 'User pool terms list.';
                $returnValue['data'] = $this->getUserPoolTerms();
            }

            if ($this->option('group')) {
                $returnValue['option'] = 'group';
                $returnValue['message'] = 'User pool groups list.';
                $returnValue['data'] = $this->getUserPoolGroups();
            }

            $this->info($returnValue['message'] ?? 'List of data retrieved successfully.');

            // Display the data in the specified format (table or json)
            if ($this->option('format') === 'json') {
                $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));
            } else {
                $this->table(
                    ['Id', 'Name', 'LastModifiedDate'],
                    $this->getTabularData($returnValue)
                );
            } //End if
            $returnValue = Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('ListCommand:handle:Exception');
            $this->components->error($exception->getMessage());
            $returnValue = Command::FAILURE;
        } // Try-catch ends
        return $returnValue;
    } //Function ends

    /**
     * Get tabular data for table output.
     *
     * @param array $responseData
     * @param array|null $columns
     *
     * @return array
     */
    private function getTabularData(array $responseData, ?array $columns=[]): array
    {
        return array_map(function($item) use ($responseData, $columns) {
            // Initialize variables
            $returnData = null;

            switch ($responseData['option']) {
                case 'pool':
                    $returnData = array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['Id', 'Name', 'LastModifiedDate']));
                        break;
                case 'client':
                    $returnData = array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['ClientId', 'ClientName']));
                        break;
                case 'term':
                    $returnData = array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['TermsId', 'TermsName', 'LastModifiedDate']));
                        break;
                case 'group':
                    $returnData = array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['GroupName', 'Description', 'LastModifiedDate']));
                        break;
                default:
                    $returnData = [];
            } // End switch
            return $returnData;
        }, $responseData['data']);
    } //Function ends

} // Class ends
