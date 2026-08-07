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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;

class ListPoolSettingTest extends TestCase
{
    /**
     * Test the list pool setting command.
     */
    #[Test]
    public function test_list_pool_setting_command(): void
    {
        // Run the command with the --config option
        $this->artisan('cognito:list-pool-setting --config')
            ->expectsOutput('User Pool Configuration:')
            ->assertExitCode(0);

        // Run the command with the --client-config option
        $this->artisan('cognito:list-pool-setting --client-config')
            ->expectsOutput('User Pool Client Configuration:')
            ->assertExitCode(0);

        // Run the command with the --mfa-config option
        $this->artisan('cognito:list-pool-setting --mfa-config')
            ->expectsOutput('User Pool MFA Configuration:')
            ->assertExitCode(0);
    } //Function ends
} //Class ends
