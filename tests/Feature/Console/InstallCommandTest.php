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

use Ellaisys\Cognito\Tests\TestCase;

#[Group('console'), Group('install-command')]
class InstallCommandTest extends TestCase
{
    /**
     * Test the install command can be cancelled.
     */
    #[Test]
    public function test_install_command_can_be_cancelled(): void
    {
        // Run the command and cancel the installation
        $this->artisan('cognito:install')
            ->expectsConfirmation(
                'Would you like to configure AWS Cognito now?',
                'no'
            )
            ->expectsOutputToContain(
                'AWS Cognito installation cancelled.'
            )
            ->assertExitCode(Command::SUCCESS);
    } //Function ends

    /**
     * Test the install command fails when AWS credentials are not confirmed.
     */
    #[Test]
    public function test_install_command_fails_when_credentials_are_not_confirmed(): void
    {
        // Run the command and decline the AWS credential confirmation
        $this->artisan('cognito:install')
            ->expectsConfirmation(
                'Would you like to configure AWS Cognito now?',
                'yes'
            )
            ->expectsConfirmation(
                'Are your AWS credentials configured and ready to use?',
                'no'
            )
            ->assertExitCode(Command::FAILURE);
    } //Function ends

    /**
     * Test the install command can be invoked.
     */
    #[Test]
    public function test_install_command_is_registered(): void
    {
        // Verify that the command is registered with Artisan
        $this->artisan('cognito:install')
            ->expectsConfirmation(
                'Would you like to configure AWS Cognito now?',
                'no'
            )
            ->assertExitCode(Command::SUCCESS);
    } //Function ends
} // Class ends
