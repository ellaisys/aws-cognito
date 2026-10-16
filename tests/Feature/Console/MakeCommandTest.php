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

use Exception;
use Ellaisys\Cognito\Exceptions\ConsoleException;
use Symfony\Component\Console\Exception\RuntimeException;

#[Group('console'), Group('make-command')]
class MakeCommandTest extends TestCase
{
    /**
     * Test the make command.
     */
    #[Test]
    #[DataProvider('makeCommandSuccessDataProvider')]
    public function test_make_commands(string $command): void
    {
        // Run the command
        $this->artisan($command)
            ->assertExitCode(Command::SUCCESS);
    } //Function ends

    /**
     * Data provider for test_make_commands.
     *
     * @return array
     */
    public static function makeCommandSuccessDataProvider(): array
    {
        return [
            'pool' => ['cognito:make --pool --name=TempUserPool --quiet'],
            'client' => ['cognito:make --client --name=TempUserClient --quiet'],
            'term of use' => ['cognito:make --term --name="terms-of-use" --detail="https://example.com/terms-of-use" --quiet'],
            'privacy policy' => ['cognito:make --term --name="privacy-policy" --detail="https://example.com/privacy-policy" --quiet'],
            'group' => ['cognito:make --group --name=TempUserGroup --detail="Temp User Group" --quiet'],
        ];
    } // Function ends

    /**
     * Test the make command with invalid arguments.
     */
    #[Test]
    public function test_make_command_invalid_arguments(): void
    {
        $this->expectException(RuntimeException::class);

        // Run the command with invalid arguments
        $this->artisan('cognito:make --invalid-argument')
            ->assertExitCode(Command::FAILURE);
    } // Function ends

    /**
     * Test the make command exceptions.
     */
    #[Test]
    #[DataProvider('makeCommandExceptionDataProvider')]
    public function test_make_command_exceptions(string $command): void
    {
        // Run the command
        $this->artisan($command)
            ->assertExitCode(Command::FAILURE);
    } // Function ends

    /**
     * Data provider for test_make_command_exceptions.
     *
     * @return array
     */
    public static function makeCommandExceptionDataProvider(): array
    {
        return [
            'invalid action command' => [
                'cognito:make --deletion-protection'
            ],
            'missing required argument' => [
                'cognito:make --pool'
            ],
            'invalid url for term of use' => [
                'cognito:make --term --name="terms-of-use" --detail="example.com/terms-of-use" --quiet'
            ],
        ];
    } // Function ends

} // Class ends
