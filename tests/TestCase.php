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

use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Workbench\Database\Seeders\DatabaseSeeder;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use Ellaisys\Cognito\Providers\AwsCognitoServiceProvider;

use InvalidArgumentException;

abstract class TestCase extends OrchestraTestCase
{
    use WithWorkbench;
    use RefreshDatabase;

    // Seed the database before each test
    protected $seed = true;
    protected $seeder = DatabaseSeeder::class;

    // Define a static properties
    protected static ?array $claim = null;

    /**
     * Define environment setup.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Automatically mock Vite for all feature tests
        $this->withoutVite();
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

    protected function getPackageProviders($app)
    {
        return [
            AwsCognitoServiceProvider::class,
        ];
    } //Function ends
} //Class ends
