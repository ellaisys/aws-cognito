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

class ListConfigCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:list-config {--pool : Get user pool configuration}
                                {--client-config : Get user pool client configuration}
                                {--mfa-config : Get user pool MFA configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List user pool configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            if (!array_filter($this->options())) {
                $this->error('Please provide at least one option: --pool, --client-config, or --mfa-config');
                return;
            }

            $returnValue = [];

            $this->info('Fetching user pool configuration...');

            if ($this->option('pool')) {
                $returnValue['message'] = 'User pool configuration.';
                $returnValue['data'] = $this->getUserPoolConfig();
            }

            if ($this->option('client-config')) {
                $returnValue['message'] = 'User pool client configuration.';
                $returnValue['data'] = $this->getUserPoolClientConfig();
            }

            if ($this->option('mfa-config')) {
                $returnValue['message'] = 'User pool MFA configuration.';
                $returnValue['data'] = $this->getUserPoolMfaConfig();
            }

            $this->info($returnValue['message'] ?? 'Configuration:');
            $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));
        } catch (Exception $exception) {
            Log::error('ListConfigCommand:handle:Exception');
            $this->error('Error retrieving configuration data.' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

} // Class ends
