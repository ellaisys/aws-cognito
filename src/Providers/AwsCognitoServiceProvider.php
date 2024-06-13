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
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

use Ellaisys\Cognito\Exceptions\Handler as AwsCognitoExceptionHandler;

/**
 * Class AwsCognitoServiceProvider.
 */
class AwsCognitoServiceProvider extends ServiceProvider
{
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //Register resources
        $this->configure();

        //Register Alias
        $this->registerAliases();

        //Register Cognito Exception Handler
        $this->registerCognitoExceptionHandler();
    } //Function ends


    public function boot()
    {  
        //Register publishing
        $this->registerPublishing();

        //Register routes
        $this->registerRoutes();

        //Register migrations
        $this->registerMigrations();

        //Register resources\
        $this->registerResources();

        //Register resources
        $this->registerPolicies();

        //Register facades
        $this->registerCognitoFacades();

        //Set Singleton Class
        $this->registerCognitoProvider();

        //Set Guards
        $this->extendWebAuthGuard();
        $this->extendApiAuthGuard();

        //Set Blade Components
        $this->registerBladeComponents();

        //Route::mixin();
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

            $this->publishes([
                __DIR__.'/../../resources/views' => $this->app->resourcePath('views/vendor/cognito'),
            ], 'cognito-views');

            //Publish Controllers
            $this->publishes([
                __DIR__.'/../../src/Http/Controllers/Api/AuthController.php' => app_path('Http/Controllers/Api/AuthController.php'),
                __DIR__.'/../../src/Http/Controllers/Api/MFAController.php' => app_path('Http/Controllers/Api/MFAController.php'),
                __DIR__.'/../../src/Http/Controllers/Api/RefreshTokenController.php' => app_path('Http/Controllers/Api/RefreshTokenController.php'),
                __DIR__.'/../../src/Http/Controllers/Api/RegisterController.php' => app_path('Http/Controllers/Api/RegisterController.php'),
                __DIR__.'/../../src/Http/Controllers/Api/UserController.php' => app_path('Http/Controllers/Api/UserController.php'),
                __DIR__.'/../../src/Http/Controllers/Auth/LoginController.php' => app_path('Http/Controllers/Auth/LoginController.php'),
            ], 'cognito-controllers');

        } //End if
    } //Function ends


    /**
     * Register the package's routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if ($this->app->routesAreCached()) {
            return;
        } //End if

        if (AwsCognito::$registersRoutes) {
            Route::group([
                'prefix' => 'api',
                'namespace' => 'Ellaisys\Cognito\Http\Controllers\Api',
                'middleware' => ['api'],
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
            });

            Route::group([
                'prefix' => config('cognito.path', ''),
                'namespace' => 'Ellaisys\Cognito\Http\Controllers',
                'middleware' => ['web'],
                'as' => 'cognito.',
            ], function () {
                $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
            });
        } //End if
    } //Function ends


    /**
     * Bind some aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias('ellaisys.aws.cognito', AwsCognito::class);
        $this->app->alias('ellaisys.aws.cognito.exception', AwsCognitoExceptionHandler::class);
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
                    new ClaimSession,
                    // new QueryString,
                    // new InputSource,
                    // new RouteParams,
                    // new Cookies($this->config('decrypt_cookies')),
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

        $this->app->singleton('ellaisys.aws.cognito', function (Application $app, array $config) {
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
        $this->app->singleton(AwsCognitoClient::class, function (Application $app) {
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
            $client = new AwsCognitoClient(
                new CognitoIdentityProviderClient($aws_config),
                config('cognito.app_client_id'),
                config('cognito.app_client_secret'),
                config('cognito.user_pool_id'),
                config('cognito.app_client_secret_allow', true)
            );

            return $client;
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
                $client = $app->make(AwsCognitoClient::class),
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
                $client = $app->make(AwsCognitoClient::class),
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


    /**
     * Register the package blade components.
     */
    protected function registerBladeComponents()
    {
        //Provision to register blade components and directives
    } //Function ends


    /**
     * Register the package exception handler.
     *
     * @return void
     */
    protected function registerCognitoExceptionHandler()
    {
        $this->app->singleton('ellaisys.aws.cognito.exception', function (Application $app) {
            return new AwsCognitoExceptionHandler(
                $app['config']['cognito']['exceptions']
            );
        });
    } //Function ends
    
} //Class ends
