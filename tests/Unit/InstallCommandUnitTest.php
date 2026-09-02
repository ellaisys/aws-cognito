<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Support\TestableInstallCommand;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('console'), Group('install-command')]
class InstallCommandUnitTest extends TestCase
{
    /**
     * @var TestableInstallCommand
     */
    protected TestableInstallCommand $command;

    /**
     * @var BufferedOutput
     */
    protected BufferedOutput $output;

    public function setUp(): void
    {
        parent::setUp();

        // Create the test command
        $this->command = app(TestableInstallCommand::class);

        // Initialize command input
        $input = new ArrayInput([]);

        // Initialize command output
        $this->output = new BufferedOutput();

        // Initialize command output
        $output = new OutputStyle(
            $input,
            $this->output
        );

        // Set command input/output
        $this->command->setInput($input);
        $this->command->setOutput($output);

        $this->initializeComponents();
    } //Function ends

    public function initializeComponents(): void
    {
        $this->components = new Factory(
            $this->output
        );
    } //Function ends

    #[Test]
    public function test_handle_completes_installation_successfully(): void
    {
        // Configure all internal operations to succeed
        $this->command->hygieneResult = Command::SUCCESS;
        $this->command->migrationResult = Command::SUCCESS;
        $this->command->environmentResult = Command::SUCCESS;
        $this->command->userPoolResult = Command::SUCCESS;
        $this->command->userGroupResult = Command::SUCCESS;

        // Run the command
        $result = $this->command->handle();

        // Verify successful execution
        $this->assertSame(Command::SUCCESS, $result);
    } //Function ends

} //Class ends
