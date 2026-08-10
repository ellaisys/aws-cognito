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
                                {--terms : Get list of user pool terms}
                                {--groups : Get list of user pool groups}
                                {--format=table : Output format (table, json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List user pools or user pool clients';

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

            $returnValue = [];

            $this->info('Fetching data...');

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

            $this->info($returnValue['message'] ?? 'List of data retrieved successfully.');

            if ($this->option('format') === 'json') {
                $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));
            } else {
                $this->table(
                    ['Id', 'Name', 'LastModifiedDate'],
                    $this->getTabularData($returnValue)
                );
            } //End if
        } catch (Exception $exception) {
            Log::error('ListCommand:handle:Exception');
            $this->error('Error retrieving configuration data.' . $exception->getMessage());
        } // Try-catch ends
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
            switch ($responseData['option']) {
                case 'pool':
                    return array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['Id', 'Name', 'LastModifiedDate']));
                case 'client':
                    return array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['ClientId', 'ClientName']));
                case 'terms':
                    return array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['TermsId', 'TermsName', 'LastModifiedDate']));
                case 'groups':
                    return array_intersect_key($item,
                        array_flip(!empty($columns) ? $columns : ['GroupName', 'Description', 'LastModifiedDate']));
                default:
                    return [];
            } // End switch
        }, $responseData['data']);
    } //Function ends

} // Class ends
