<?php

namespace Ellaisys\Cognito\Tests;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use Ellaisys\Cognito\Providers\AwsCognitoServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    use WithWorkbench;

    // Load your library's service provider
    protected function getPackageProviders($app)
    {
        return [
            AwsCognitoServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $awsKey = config('cognito.credentials.key');

        if ($awsKey && ! Str::startsWith($awsKey, 'ak_test_')) {
            throw new InvalidArgumentException('Tests may not be run with a production Cognito key.');
        }
    }
}
