<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Providers;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoManager;
use Ellaisys\Cognito\AwsCognitoUserPool;
use Ellaisys\Cognito\Guards\CognitoSessionGuard;
use Ellaisys\Cognito\Guards\CognitoTokenGuard;
use Ellaisys\Cognito\Services\AwsCognitoJwksService;

use Ellaisys\Cognito\Http\Parser\Parser;
use Ellaisys\Cognito\Http\Parser\AuthHeaders;
use Ellaisys\Cognito\Http\Parser\ClaimSession;

use Ellaisys\Cognito\Providers\StorageProvider;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Application;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

/**
 * Class AwsCognitoServiceProvider.
 */
class AwsCognitoServiceProvider extends ServiceProvider
{

    //Laravel version
    protected $laravelVersion;
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //Set Laravel Version
        $this->setLaravelVersion();

        //Register resources
        $this->configure();

        //Register Alias
        $this->registerAliases();
    } //Function ends


    public function boot()
    {
        //Register publishing
        $this->registerPublishing();

        //Register migrations
        $this->registerMigrations();

        //Register resources
        $this->registerPolicies();

        //Register facades
        $this->registerCognitoFacades();

        //Set Singleton Class
        $this->registerCognitoProvider();

        //Set Guards
        $this->extendWebAuthGuard();
        $this->extendApiAuthGuard();
    } //Function ends


    /**
     * Getter and Setter for Laravel Version
     *
     * @return string
     */
    public function getLaravelVersion(): string
    {
        return $this->laravelVersion;
    } //Function ends
    public function setLaravelVersion(): void
    {
        $laravelVersion = Application::VERSION;
        $this->laravelVersion = $laravelVersion;
    } //Function ends


    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            //Publish config
            $this->publishes([
                __DIR__.'/../../config/cognito.php' => $this->app->configPath('cognito.php'),
            ], 'cognito-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => $this->app->databasePath('migrations'),
            ], 'cognito-migrations');
        }
    } //Function ends


    /**
     * Bind some aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias('ellaisys.aws.cognito', AwsCognito::class);
    }

    
    /**
     * Setup the configuration for Cognito.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/cognito.php', 'cognito'
        );
    } //Function ends


    /**
     * Register Cognito Facades
     *
     * @return void
     */
    protected function registerCognitoFacades()
    {
        //Request Parser
        $this->app->singleton('ellaisys.aws.cognito.parser', function (Application $app) {
            $parser = new Parser(
                $app['request'],
                [
                    new AuthHeaders,
                    new ClaimSession
                ]
            );

            $app->refresh('request', $parser, 'setRequest');

            return $parser;
        });

        //Storage Provider
        $this->app->singleton('ellaisys.aws.cognito.provider.storage', function (Application $app) {
            return new StorageProvider(
                config('cognito.storage_provider')
            );
        });

        //Aws Cognito Manager
        $this->app->singleton('ellaisys.aws.cognito.manager', function (Application $app) {
            return new AwsCognitoManager(
                $app['ellaisys.aws.cognito.provider.storage']
            );
        });

        $this->app->singleton('ellaisys.aws.cognito', function (Application $app) {
            return new AwsCognito(
                $app['ellaisys.aws.cognito.manager'],
                $app['ellaisys.aws.cognito.parser']
            );
        });

        //JWKS Service
        $this->app->singleton(AwsCognitoJwksService::class, function () {
            return new AwsCognitoJwksService(
                config('cognito.region'),
                config('cognito.user_pool_id')
            );
        });
    } //Function ends


    /**
     * Register Cognito Provider
     *
     * @return void
     */
    protected function registerCognitoProvider()
    {
        $this->app->singleton(AwsCognitoClient::class, function () {
            $aws_config = [
                'region'      => config('cognito.region'),
                'version'     => config('cognito.version')
            ];

            //Set AWS Credentials
            $credentials = config('cognito.credentials');
            if (! empty($credentials['key']) && ! empty($credentials['secret'])) {
                $aws_config['credentials'] = Arr::only($credentials, ['key', 'secret', 'token']);
            } //End if

            //Instancite the AWS Cognito Client
            return new AwsCognitoClient(
                new CognitoIdentityProviderClient($aws_config),
                config('cognito.app_client_id'),
                config('cognito.app_client_secret'),
                config('cognito.user_pool_id'),
                config('cognito.app_client_secret_allow', true)
            );
        });

        $this->app->singleton(AwsCognitoUserPool::class, function (Application $app) {
            return new AwsCognitoUserPool($app[AwsCognitoClient::class]);
        });
    } //Function ends


    /**
     * Extend Cognito Web/Session Auth.
     *
     * @return void
     */
    protected function extendWebAuthGuard()
    {
        Auth::extend('cognito-session', function (Application $app, $name, array $config) {
            $guard = new CognitoSessionGuard(
                $name,
                $app['ellaisys.aws.cognito'],
                $app->make(AwsCognitoClient::class),
                $app['auth']->createUserProvider($config['provider']),
                $app['session.store'],
                $app['request']
            );

            $guard->setCookieJar($this->app['cookie']);
            $guard->setDispatcher($this->app['events']);
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    } //Function ends


    /**
     * Extend Cognito Api Auth.
     *
     * @return void
     */
    protected function extendApiAuthGuard()
    {
        Auth::extend('cognito-token', function (Application $app, $name, array $config) {

            $guard = new CognitoTokenGuard(
                $app['ellaisys.aws.cognito'],
                $app->make(AwsCognitoClient::class),
                $app['request'],
                Auth::createUserProvider($config['provider']),
                config('cognito.cognito_user_fields.email', 'email')
            );

            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    } //Function ends


    /**
     * Register the package resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'cognito');
    } //Function ends


    /**
     * Register the package migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        if (AwsCognito::$runsMigrations && $this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        } //End if
    } //Function ends
    
} //Class ends
