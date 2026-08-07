<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Ellaisys\Cognito\Console;

/**
 * Class ConsoleServiceProvider.
 */
class ConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->provides());
        }
    }

    public function provides(): array
    {
        return self::defaultCommands()->toArray();
    }

    /**
     * Get the package default commands.
     */
    public static function defaultCommands(): Collection
    {
        return collect([
            // Actions Commands
            Console\Actions\ListConfigCommand::class,
            Console\Actions\SyncConfigCommand::class,
        ]);
    } //Function ends

} //Class ends
