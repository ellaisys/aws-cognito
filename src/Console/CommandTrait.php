<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

trait CommandTrait
{

    protected function setEnvValue(string $key, string|bool $value): bool
    {
        $path = app()->environmentFilePath();

        if (! File::exists($path)) {
            return false;
        }

        $env = File::get($path);

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } else if (is_string($value)) {
            $value = '"' . addslashes($value) . '"';
        }

        if (preg_match("/^{$key}=.*/m", $env)) {
            $env = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $env
            );
        } else {
            $env .= PHP_EOL . "{$key}={$value}";
        }

        File::put($path, $env);

        // Clear the config cache so Laravel registers the changes
        Artisan::call('config:clear');

        // Update on the screen
        $this->info("Updated .env {$key}: {$value}");

        return true;
    } //Function ends

} // Trait ends
