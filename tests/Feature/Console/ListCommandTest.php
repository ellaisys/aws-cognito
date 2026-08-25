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

#[Group('console'), Group('list-command')]
class ListCommandTest extends TestCase
{
    /**
     * Test the list command with the --pool option.
     */
    #[Test]
    #[DataProvider('ListCommandsProvider')]
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
    public static function ListCommandsProvider(): array
    {
        return [
            'pool' => ['cognito:list --pool'],
            'client' => ['cognito:list --client'],
            'term' => ['cognito:list --term'],
            'group' => ['cognito:list --group'],
            'pool and tabular format' => ['cognito:list --pool --format=table'],
            'client and tabular format' => ['cognito:list --client --format=table'],
            'term and tabular format' => ['cognito:list --term --format=table'],
            'group and tabular format' => ['cognito:list --group --format=table'],
            'pool config' => ['cognito:list-config --pool'],
            'client config' => ['cognito:list-config --client'],
            'mfa config' => ['cognito:list-config --mfa'],
        ];
    } //Function ends

} //Class ends
