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

class ListConfigCommand extends Command
{
    use AwsCognitoTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:list-config {--pool : Get user pool configuration}
                                {--client : Get user pool client configuration}
                                {--mfa : Get user pool MFA configuration}';

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
        // Initialize return value
        $returnValue = Command::SUCCESS;

        try {

            if (!array_filter($this->options())) {
                throw new ConsoleException('Provide at least one option: --pool, --client, or --mfa');
            }

            $returnValue = [];

            $this->info('Fetching data...');

            if ($this->option('pool')) {
                $returnValue['message'] = 'User pool configuration.';
                $returnValue['data'] = $this->getUserPoolConfig();
            }

            if ($this->option('client')) {
                $returnValue['message'] = 'User pool client configuration.';
                $returnValue['data'] = $this->getUserPoolClientConfig();
            }

            if ($this->option('mfa')) {
                $returnValue['message'] = 'User pool MFA configuration.';
                $returnValue['data'] = $this->getUserPoolMfaConfig();
            }

            $this->info($returnValue['message'] ?? 'Configuration:');
            $this->info(json_encode($returnValue['data'] ?? [], JSON_PRETTY_PRINT));

            $returnValue = Command::SUCCESS;
        } catch (Exception $exception) {
            Log::error('ListConfigCommand:handle:Exception');
           $this->components->error($exception->getMessage());
            $returnValue = Command::FAILURE;
        } // Try-catch ends
        return $returnValue;
    } //Function ends

} // Class ends
