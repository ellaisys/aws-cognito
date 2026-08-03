<?php

namespace Ellaisys\Cognito\Tests;

use Illuminate\Support\Str;
use Illuminate\Contracts\Config\Repository;

use InvalidArgumentException;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use Ellaisys\Cognito\Providers\AwsCognitoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use WithWorkbench;

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
}
