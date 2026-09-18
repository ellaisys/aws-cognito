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
     * @param string|bool|int|array|null $value
     * @return bool
     */
    final public function setEnv(string $key,
        string|bool|int|array|null $value): bool
    {
        $path = app()->environmentFilePath();

        // Check if the .env file exists
        if (! File::exists($path)) {
            return false;
        } // End if

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

        // Update the existing key or add a new one if it doesn't exist
        if (preg_match("/^{$key}=.*/m", $env)) {
            $env = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $env
            );
        } else {
            $env .= PHP_EOL . "{$key}={$value}";
        } // End if

        // Write the updated content back to the .env file
        File::put($path, $env);

        // Clear the config cache so Laravel registers the changes
        Artisan::call('config:clear');

        // Update on the screen
        $this->newLine();
        $this->line("✓ Updated {$key}: {$value}");
        
        return true;
    } //Function ends

    /**
     * Delete a key from the .env file.
     *
     * @param string $key
     * @param bool|null $softDelete
     * @return bool
     */
    final public function delEnv(string $key, ?bool $softDelete = true): bool
    {
        $path = app()->environmentFilePath();

        // Check if the .env file exists
        if (! File::exists($path)) {
            return false;
        } // End if

        // Read the current content of the .env file
        $env = File::get($path);

        // Pattern to match the key in the .env file
        $pattern = "/^{$key}=.*/m";

        // Check if the key exists in the .env file before attempting to delete it
        if (preg_match($pattern, $env)) {
            // Remove the key from the .env file
            if ($softDelete) {
                $env = preg_replace($pattern, '# $0', $env);
            } else {
                $env = preg_replace($pattern, '', $env);
            } // End if

            // Write the updated content back to the .env file
            File::put($path, $env);

            // Clear the config cache so Laravel registers the changes
            Artisan::call('config:clear');

            // Update on the screen
            $this->newLine();
            $this->line("✓ Deleted {$key}");            
        } // End if

        return true;
    } //Function ends

    /**
     * Set a key in the .env file conditionally based on the existing configuration value.
     *
     * @param string $key
     * @param string|bool|int|array|null $value
     * @param string $configKey
     * @return bool
     */
    final public function setEnvConditionally(string $key,
        string|bool|int|array|null $value, string $configKey): bool
    {
        // Get the existing value from the configuration for the given key.
        $existingValue = config($configKey);

        // Convert the value to a string representation
        if (is_array($existingValue)) {
            if ($configKey === 'cognito.password_policy') {
                $existingValue = base64_encode(json_encode($existingValue));
            } else {
                $existingValue = implode(',', $existingValue);
            } // End if
        } // End if

        // Compare the existing value with the new value and update only if they differ.
        if ($existingValue !== $value) {
            return $this->setEnv($key, $value);
        } // End if

        return false;
    } //Function ends

} // Trait ends
