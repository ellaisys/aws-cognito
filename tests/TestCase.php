<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests;

use Illuminate\Support\Facades\Config;

use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

use Workbench\Database\Seeders\DatabaseSeeder;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use Ellaisys\Cognito\Providers\AwsCognitoServiceProvider;

use InvalidArgumentException;

abstract class TestCase extends OrchestraTestCase
{
    use WithWorkbench;
    use RefreshDatabase;
    use InteractsWithViews;

    public const APPLICATION_JSON = 'application/json';

    public const SAMPLE_DATA = [
        'device_key' => 'sample_device_key',
        'device_name' => 'sample_device_name',
    ];

    public const SAMPLE_INVALID_DATA = [
        'user_pool_id' => 'invalid_user_pool_id',
        'username' => 'invalid_username',
        'password' => 'InvalidPassword123!',
        'email' => 'invalid_email@example.com',
        'access_token' => 'invalid_access_token',
        'refresh_token' => 'invalid_refresh_token',
        'session_value' => 'invalid_session_value_of_20_characters',
        'device_key' => 'invalid_device_key',
        'device_name' => 'invalid_device_name',
        'pagination_token' => 'invalid_pagination_token',
        'confirmation_code' => 'invalid_confirmation_code',
        'client_metadata' => ['invalid_key' => 'invalid_value'],
        'attributes' => ['email' => 'some_invalid_email@example.com'],
        'message_action' => 'invalid_message_action',
        'user_group_name' => 'invalid_user_group_name',
    ];

    // Seed the database before each test
    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    // Define a static properties
    protected static ?array $claim = null;

    protected static $sessionAuthenticated = null;

    /**
     * Define environment setup.
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.registration_enabled', true);
        Config::set('cognito.allow_phone_number', false);
        Config::set('cognito.force_new_user_password', false);
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
        Config::set('cognito.desired_delivery_mediums', ['EMAIL']);

        // Automatically mock Vite for all feature tests
        $this->withoutVite();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);

            // Ensure session driver is set for testing
            $config->set('session.driver', 'array');
        });
    }

    /**
     * Get valid credentials from the defined constant.
     *
     * @return array
     * @throws InvalidArgumentException if the constant is not defined or invalid.
     */
    protected function getValidCredentials(): array
    {
        $validCredentialsEncodedJson = getenv('AUTH_VALID_CREDENTIALS') ?? null;

        if (!$validCredentialsEncodedJson) {
            throw new InvalidArgumentException('The "AUTH_VALID_CREDENTIALS" constant is not defined.');
        }

        $validCredentialsJson = base64_decode($validCredentialsEncodedJson, true);
        $validCredentials = $validCredentialsJson ? json_decode($validCredentialsJson, true) : null;

        return $validCredentials ?? [];
    } //Function ends

    /**
     * Get invalid credentials.
     *
     * @return array
     */
    protected function getInvalidCredentials(): array
    {
        return [
            'username' => self::SAMPLE_INVALID_DATA['username'],
            'password' => self::SAMPLE_INVALID_DATA['password'],
            'email' => self::SAMPLE_INVALID_DATA['email'],
        ];
    } //Function ends

    protected function getPackageProviders($app)
    {
        return [
            AwsCognitoServiceProvider::class,
        ];
    } //Function ends
} //Class ends
