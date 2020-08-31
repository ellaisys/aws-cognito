<?php

namespace Ellaisys\Cognito;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

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
        $this->mergeConfigFrom(__DIR__.'/../../config/aws-cognito.php', 'cognito');

        // $configPath = __DIR__.'/../config/config.php'; //base_path('packages/ellaisys/exotel/config/config.php');

        // //Register configuration
        // $this->mergeConfigFrom($configPath, 'ellaisys-exotel'); 

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
    }


    public function boot()
    {
        //Publish config
        $this->publishes([
            __DIR__.'/../config/aws-cognito.php' => config_path('cognito.php'),
        ], 'config');

        //Set Singleton Class
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

            return new CognitoClient(
                new CognitoIdentityProviderClient($aws_config),
                config('cognito.app_client_id'),
                config('cognito.app_client_secret'),
                config('cognito.user_pool_id')
            );
        });

        $this->app['auth']->extend('cognito', function (Application $app, $name, array $config) {
            $guard = new CognitoGuard(
                $name,
                $client = $app->make(CognitoClient::class),
                $app['auth']->createUserProvider($config['provider']),
                $app['session.store'],
                $app['request']
            );

            $guard->setCookieJar($this->app['cookie']);
            $guard->setDispatcher($this->app['events']);
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
    
} //Class ends