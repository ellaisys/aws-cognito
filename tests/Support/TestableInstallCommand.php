<?php

namespace Ellaisys\Cognito\Tests\Support;

use Illuminate\Console\Command;

use Ellaisys\Cognito\Console\Actions\InstallCommand;

class TestableInstallCommand extends InstallCommand
{
    public int $hygieneResult = Command::SUCCESS;
    public int $migrationResult = Command::SUCCESS;
    public int $environmentResult = Command::SUCCESS;
    public int $userPoolResult = Command::SUCCESS;
    public int $userGroupResult = Command::SUCCESS;

    public bool $confirmResult = true;

    protected function checkHygieneData(): int
    {
        return $this->hygieneResult;
    } //Function ends

    protected function promptUserForDatabaseMigration(): int
    {
        return $this->migrationResult;
    } //Function ends

    protected function setEnvironment(): int
    {
        return $this->environmentResult;
    } //Function ends

    protected function getUserPoolId(): int
    {
        return $this->userPoolResult;
    } //Function ends

    protected function promptUserForUserGroups(): int
    {
        return $this->userGroupResult;
    } //Function ends

    public function confirm($question, $default = true, $attempts = null, $multiple = null): bool
    {
        return $this->confirmResult;
    } //Function ends
} //Class ends
