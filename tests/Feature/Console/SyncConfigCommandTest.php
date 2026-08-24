<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Feature\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Bootstrap\HandleExceptions;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;

#[Group('console'), Group('sync-command')]
class SyncConfigCommandTest extends TestCase
{

    /**
     * Test the sync command.
     */
    #[Test]
    public function test_sync_command_with_pool_option(): void
    {
        try {
            // Run the command with the --aws-to-local --pool option
            $this->artisan('cognito:sync --aws-to-local --pool')
                ->assertExitCode(Command::SUCCESS);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail($e->getMessage());
        }
    } //Function ends

    #[Test]
    public function test_sync_command_with_client_option(): void
    {
        try {
            // Run the command with the --local-to-aws --client option
            $this->artisan('cognito:sync --local-to-aws --client')
                ->assertExitCode(Command::SUCCESS);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail($e->getMessage());
        }
    } //Function ends

    #[Test]
    public function test_sync_command_with_mfa_option(): void
    {
        try {
            // Run the command with the --local-to-aws --mfa option
            $this->artisan('cognito:sync --local-to-aws --mfa')
                ->assertExitCode(Command::SUCCESS);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail($e->getMessage());
        }
    } //Function ends
} //Class ends
