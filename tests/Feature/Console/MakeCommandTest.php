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

#[Group('console'), Group('make-command')]
class MakeCommandTest extends TestCase
{
    /**
     * Test the make command.
     */
    #[Test]
    #[DataProvider('MakeCommandsProvider')]
    public function test_make_commands(string $command): void
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
     * Data provider for test_make_commands.
     *
     * @return array
     */
    public static function MakeCommandsProvider(): array
    {
        return [
            'pool' => ['cognito:make --pool --name=TempUserPool --quiet'],
            'client' => ['cognito:make --client --name=TempUserClient --quiet'],
            'term of use' => ['cognito:make --term --name="terms-of-use" --detail="https://example.com/terms-of-use" --quiet'],
            'privacy policy' => ['cognito:make --term --name="privacy-policy" --detail="https://example.com/privacy-policy" --quiet'],
            'group' => ['cognito:make --group --name=TempUserGroup --detail="Temp User Group" --quiet'],
        ];
    } //Function ends
} //Class ends
