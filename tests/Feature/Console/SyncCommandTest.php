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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;

use Ellaisys\Cognito\Tests\TestCase;

#[Group('console'), Group('sync-command')]
class SyncCommandTest extends TestCase
{
    /**
     * Test the sync command.
     */
    #[Test]
    #[DataProvider('SyncCommandsProvider')]
    public function test_sync_command(string $command): void
    {
        try {
            // Run the command
            $this->artisan($command)
                ->assertExitCode(Command::SUCCESS);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail($e->getMessage());
        }
    } //Function ends

    /**
     * Data provider for test_sync_commands.
     *
     * @return array
     */
    public static function SyncCommandsProvider(): array
    {
        return [
            'pool' => ['cognito:sync --aws-to-local --pool'],
            'client' => ['cognito:sync --aws-to-local --client'],
            'mfa' => ['cognito:sync --aws-to-local --mfa'],
            'pool-local' => ['cognito:sync --local-to-aws --pool']
        ];
    } //Function ends

    /**
     * Test the sync command without options expecting failure.
     */
    #[Test]
    #[DataProvider('listFailureCommandsProvider')]
    public function test_sync_commands_without_options(string $command): void
    {
        try {
            // Run the command without any options
            $this->artisan($command)
                ->assertExitCode(Command::FAILURE);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail($e->getMessage());
        }
    } //Function ends

    /**
     * Data provider for test_sync_commands_without_options.
     *
     * @return array
     */
    public static function listFailureCommandsProvider(): array
    {
        return [
            'no options' => ['cognito:sync'],
            'wrong pool' => ['cognito:sync --aws-to-local --pool --pool-id=wrong'],
            'wrong client' => ['cognito:sync --aws-to-local --client --client-id=wrong'],
            'wrong mfa' => ['cognito:sync --aws-to-local --mfa --pool-id=wrong'],
            'wrong local to aws pool' => ['cognito:sync --local-to-aws --pool --pool-id=wrong'],
        ];
    } //Function ends

} //Class ends
