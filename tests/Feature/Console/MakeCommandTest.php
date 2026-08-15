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
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;

class MakeCommandTest extends TestCase
{

    /**
     * Test the make command.
     */
    #[Test]
    public function test_make_command_with_pool_option(): void
    {
        try {
            // Run the command with the --pool option
            $this->artisan('cognito:make TempUserPool --pool --quiet')
                ->assertExitCode(Command::SUCCESS);

        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail('Exception during bootstrapping: ' . $e->getMessage());
        }
    } //Function ends
} //Class ends
