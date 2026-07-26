<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;

class PoolCommand extends Command
{
    use CommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cognito:pool-setting';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pool setting description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getUserPoolMfaConfig();
    }

    private function getUserPoolMfaConfig()
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get user pool MFA configuration
            $response = $client->getUserPoolMfaConfig();

            $this->info('User Pool MFA Configuration:');
            $this->info(json_encode($response->toArray(), JSON_PRETTY_PRINT));

        } catch (Exception $exception) {
            $this->error('Error retrieving user pool MFA configuration: ' . $exception->getMessage());
        } // Try-catch ends
    } //Function ends

} // Class ends
