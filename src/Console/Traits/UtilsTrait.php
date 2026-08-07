<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Console\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

trait UtilsTrait
{
    /**
     * Set the value of a key in the .env file.
     *
     * @param string $key
     * @param string|bool|int|array $value
     * @return bool
     */
    protected function setEnv(string $key, string|bool|int|array $value): bool
    {
        $path = app()->environmentFilePath();

        // Check if the .env file exists
        if (! File::exists($path)) {
            return false;
        }

        // Read the current content of the .env file
        $env = File::get($path);

        // Convert the value to a string representation
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            $value = '"' . addslashes($value) . '"';
        } elseif (is_array($value)) {
            $value = '"' . addslashes(json_encode($value)) . '"';
        } else {
            $value = (string) $value;
        } //End if

        if (preg_match("/^{$key}=.*/m", $env)) {
            $env = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $env
            );
        } else {
            $env .= PHP_EOL . "{$key}={$value}";
        }

        // Write the updated content back to the .env file
        File::put($path, $env);

        // Clear the config cache so Laravel registers the changes
        Artisan::call('config:clear');

        // Update on the screen
        $this->info("Updated .env {$key}: {$value}");

        return true;
    } //Function ends

} // Trait ends
