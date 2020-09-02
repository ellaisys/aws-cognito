<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito;

use Ellaisys\Cognito\Guards\CognitoRequestGuard;

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
    
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //Register configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/aws-cognito.php', 'cognito');

        // //Register the singletons
        // $this->app->singleton(ExotelCall::class, function () {
        //     return new ExotelCall();
        // });
        // // $this->app->singleton(ExotelSms::class, function () {
        // //     return new ExotelSms();
        // // });

        // //Bind Facades
        // $this->app->alias(ExotelCall::class, 'exotel-call');
        // // $this->app->bind('exotel-call', function($app) {
        // //     return new ExotelCall();
        // // });
    } //Function ends


    public function boot()
    {
        //Publish config
        $this->publishes([
            __DIR__.'/../config/aws-cognito.php' => config_path('cognito.php'),
        ], 'config');

        //Set Singleton Class
        $this->registerCognitoProvider();

        //Set Guards
        $this->extendWebAuthGuard();
        $this->extendApiAuthGuard();
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

            return new AwsCognitoClient(
                new CognitoIdentityProviderClient($aws_config),
                config('cognito.app_client_id'),
                config('cognito.app_client_secret'),
                config('cognito.user_pool_id')
            );
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
            $guard = new CognitoGuard(
                $name,
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
        Auth::extend('cognito-token', function ($app, $name, array $config) {
            $guard = new CognitoRequestGuard(
                $app['tymon.jwt'],
                $client = $app->make(AwsCognitoClient::class),
                $app['request'],
                Auth::createUserProvider($config['provider'])
            );

            $guard->setDispatcher($this->app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    } //Function ends
    
} //Class ends