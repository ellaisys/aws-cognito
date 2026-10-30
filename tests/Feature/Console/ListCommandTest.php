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

#[Group('console'), Group('list-command')]
class ListCommandTest extends TestCase
{
    /**
     * Test the list command with the --pool option.
     */
    #[Test]
    #[DataProvider('listCommandsProvider')]
    public function test_list_commands(string $command): void
    {
        try {
            // Run the command with the --pool option
            $this->artisan($command)
                ->assertExitCode(Command::SUCCESS);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail($e->getMessage());
        }
    } //Function ends

    /**
     * Data provider for test_list_commands.
     *
     * @return array
     */
    public static function listCommandsProvider(): array
    {
        return [
            'pool' => ['cognito:list --pool'],
            'client' => ['cognito:list --client'],
            'term' => ['cognito:list --term'],
            'group' => ['cognito:list --group'],
            'pool and json format' => ['cognito:list --pool --format=json'],
            'pool and tabular format' => ['cognito:list --pool --format=table'],
            'client and tabular format' => ['cognito:list --client --format=table'],
            'term and tabular format' => ['cognito:list --term --format=table'],
            'group and tabular format' => ['cognito:list --group --format=table'],
            'pool config' => ['cognito:list-config --pool'],
            'client config' => ['cognito:list-config --client'],
            'mfa config' => ['cognito:list-config --mfa'],
        ];
    } //Function ends

    /**
     * Test the list command expecting failure.
     */
    #[Test]
    #[DataProvider('listFailureCommandsProvider')]
    public function test_list_commands_without_options(string $command): void
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
     * Data provider for test_list_commands.
     *
     * @return array
     */
    public static function listFailureCommandsProvider(): array
    {
        return [
            'no options' => ['cognito:list'],
            'no config argument' => ['cognito:list-config'],
        ];
    } //Function ends

    /**
     * Test the list command with an unknown option.
     */
    #[Test]
    public function test_list_command_with_unknown_option(): void
    {
        try {
            $this->artisan('cognito:list --invalid');

            $this->fail('Expected an exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertSame(
                'The "--invalid" option does not exist.',
                $e->getMessage()
            );
        }
    } //Function ends

    /**
     * Test the list command with the pool option without a value.
     */
    #[Test]
    public function test_list_command_with_pool_option_without_value(): void
    {
        try {
            $this->artisan('cognito:list --pool=');

            $this->fail('Expected an exception was not thrown.');
        } catch (\Throwable $e) {
            $this->assertSame(
                'The "--pool" option does not accept a value.',
                $e->getMessage()
            );
        }
    } //Function ends

} //Class ends
