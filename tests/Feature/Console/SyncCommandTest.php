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
        ];
    } //Function ends

} //Class ends
