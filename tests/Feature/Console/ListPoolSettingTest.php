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

use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Bootstrap\HandleExceptions;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;

class ListPoolSettingTest extends TestCase
{

    /**
     * Test the list pool setting command.
     */
    #[Test]
    public function test_list_pool_setting_command(): void
    {
        try {
            // Run the command with the --pool option
            $this->artisan('cognito:list-config --pool')
                ->expectsOutput('User pool configuration.')
                ->assertExitCode(0);

            // Run the command with the --client option
            $this->artisan('cognito:list-config --client')
                ->expectsOutput('User pool client configuration.')
                ->assertExitCode(0);

            // Run the command with the --mfa option
            $this->artisan('cognito:list-config --mfa')
                ->expectsOutput('User pool MFA configuration.')
                ->assertExitCode(0);
        } catch (\Throwable $e) {
            // Handle any exceptions that occur during bootstrapping
            $this->fail('Exception during bootstrapping: ' . $e->getMessage());
        }
    } //Function ends
} //Class ends
