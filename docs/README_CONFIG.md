# Configurations

> [!NOTE]
> Last Updated: <!-- AUTO:last_updated -->2026-07-31<!-- /AUTO:last_updated -->

This document provides guidance on configuring the AWS Cognito service and the Laravel application to work with AWS Cognito. It is intended for developers who are familiar with Laravel and AWS services.


## Contents

- [AWS Configurations](COGNITOCONFIG.md)
    + [AWS IAM configuration](COGNITOCONFIG.md#aws-iam-configuration)
    + [AWS Cognito configuration](COGNITOCONFIG.md#aws-cognito-configuration)

- [Laravel Configurations](#laravel-configurations)
    + [ServiceProvider Registration](#serviceprovider-registration)
    + [Registering the Middleware](#registering-the-middleware)
    + [Environment Variables](#environment-variables)
    + [Environment Variables - Optional](#additional-environment-variables-optional)
        * Session Timeout Configuration
        * Support for App Client without Secret Enabled

    + [Changes in Auth Configurations](#changes-in-auth-configurations)
    + [Publishing Configurations](#publishing-configurations)
    + [Database Configurations](#database-configurations)
    + [Model Configurations](#model-configurations)
        * User Model

    + [Session Storage Configurations](#session-storage-configurations)
        * DynamoDB Storage

- [References](#references)


## AWS Configurations

This package uses the AWS Cognito Services to provide authentication and authorization services for your Laravel application. To create an account with AWS, please refer to the [Amazon Management Console](https://console.aws.amazon.com/cognito/home).

The AWS configurations are required to be set up in order to use the AWS Cognito service. The detailed steps for setting up the AWS Cognito service are provided in the [AWS Configurations](COGNITOCONFIG.md) document. Please refer to that document for detailed instructions on how to set up the AWS Cognito service.


## Laravel Configurations

Laravel configurations are required to be set up in order to use the AWS Cognito service with your Laravel application. The following sections provide detailed instructions on how to set up the Laravel configurations.

Make use of the cognito console commands to automatically set up the configurations in your Laravel application. Refer to the [Cognito Console Commands](COGNITOCONSOLE.md) document for detailed instructions on how to use the console commands.

### *ServiceProvider Registration*

*<u>Laravel 5.4 and before</u>*

Using a version prior to Laravel 5.5 you need to manually register the service provider in your `bootstrap/app.php` file:

```php
// bootstrap/app.php
'providers' => [
    ...
    Ellaisys\Cognito\Providers\AwsCognitoServiceProvider::class,
];
```

*<u>Laravel 5.5 and above</u>*

With Laravel versions 5.5 and above, the service provider is automatically registered using Laravel's package auto-discovery feature. You do not need to manually register the service provider in your `bootstrap/app.php` file.


### *Registering the Middleware*

To use the AWS Cognito middleware, you need to register it in your Laravel application. The middleware is responsible for handling the authentication and authorization of requests to your application.

*<u>Laravel 10.0 and below</u>*

In case you are using this library as API driver, you can register the middleware into the `app/Http/Kernel.php` in the `$routeMiddleware` array as shown below:

```php
// app/Http/Kernel.php
protected $routeMiddleware = [
    ...
    'aws-cognito' => \Ellaisys\Cognito\Http\Middleware\AwsCognitoAuthenticate::class
];
```

*<u>Laravel 11.0 and above</u>*

The middleware congiguration is defined in the `bootstrap/app.php` file. Please configure as shown below

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ...
    ->withMiddleware(function (Middleware $middleware): void {
        ...
        $middleware->alias([
            ...
            'aws-cognito' => \Ellaisys\Cognito\Http\Middleware\AwsCognitoAuthenticate::class
        ]);
        ...
    })
    ...
```

You can then use the middleware in your routes or controllers to protect your application routes. For example, you can use the middleware in your `routes/web.php` file as shown below:

```php
// routes/web.php
Route::middleware(['aws-cognito'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```


### *Environment Variables*

In order to use AWS Cognito, you will need to add the following minimum configurations to your Laravel application. You can do this by adding the following fields to your `.env` file:

```env
# AWS configurations for cloud storage
AWS_ACCESS_KEY_ID="Axxxxxxxxxxxxxxxxxxxxxxxx6"
AWS_SECRET_ACCESS_KEY="mxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx+"
AWS_DEFAULT_REGION="xxxxxxxxxxx" //optional - default value is 'us-east-1'

# AWS Cognito configurations
AWS_COGNITO_CLIENT_ID="6xxxxxxxxxxxxxxxxxxxxxxxxr"
AWS_COGNITO_CLIENT_SECRET="1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx1"
AWS_COGNITO_USER_POOL_ID="xxxxxxxxxxxxxxxxx"
AWS_COGNITO_REGION="xxxxxxxxxxx" //optional - default value is 'us-east-1'
AWS_COGNITO_VERSION="latest" //optional - default value is 'latest'
```

For more details on how to find `AWS_COGNITO_CLIENT_ID`, `AWS_COGNITO_CLIENT_SECRET` and `AWS_COGNITO_USER_POOL_ID` for your application, please refer to the [AWS Configurations](#aws-configurations).

The console command `php artisan cognito:install` can be used to automatically add the above configurations to your `.env` file. You can run this command after installing the package.


### *Additional Environment Variables* (Optional)

The following environment variables are optional and can be used to customize the behavior of the AWS Cognito service. You can add these fields to your `.env` file as per your requirements. The default values are provided in the `config/cognito.php` file. You can change these values as per your requirements.


#### Session Timeout Configuration

You can configure the session timeout in your `.env` file, aligned with the cognito access token validity, use the `SESSION_LIFETIME` and `AUTH_PASSWORD_TIMEOUT` parameters. This value is in minutes with the default value being 120 mins i.e. 2 hours. This will ensure that the laravel session times out at the same time as the access token. For example:

```env
SESSION_LIFETIME=120 // in minutes
AUTH_PASSWORD_TIMEOUT=7200 // in seconds
```

The `SESSION_LIFETIME` parameter can be traced into the file `config/session.php` under the `lifetime` key. The `AUTH_PASSWORD_TIMEOUT` parameter is used to set the password timeout for the Laravel application. The default value for `AUTH_PASSWORD_TIMEOUT` is 10800 seconds (i.e. 3 hours) and can be traced into the file `config/auth.php` under the `passwords.users.expire` key. You can change these values as per your requirements.


#### Support for App Client without Secret Enabled

The library now supports where the AWS configuration of App Client with the Client Secret set to disabled. Use the below configuration into the environment file to enable/disable this. The default is marked as **true** (i.e. we expect the App Client Secret to be enabled in AWS Cognito configuration)

```env
AWS_COGNITO_CLIENT_SECRET_ALLOW=false
```


### *Changes in Auth Configurations*

In order to use AWS Cognito as your authentication driver, you will need to make the following changes to your `config/auth.php` file:
```php
'guards' => [
    'web' => [
        'driver' => 'cognito-session', // This line is important for using AWS Cognito as Web Driver
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'cognito-token', // This line is important for using AWS Cognito as API Driver
        'provider' => 'users',
    ],
],
```


### *Publishing Configurations* (Optional)

You can publish the AWS Cognito configuration file using the following command:
```sh
php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider" --tag="config"
```


### *Database Configurations*

We are using Laravel's built-in database migration system to manage the database schema for AWS Cognito. We are assuming that you have already configured your database connection in the `.env` file. If you haven't done so, please refer to the [Laravel Database Configuration](https://laravel.com/docs/10.x/database#configuration) documentation for more information.

The AWS Cognito service provider registers its own database migration directory, so remember to migrate your database after installing the package. The AWS Cognito migrations will add a few columns to your **users** table:

```sh
php artisan migrate
```

> [!IMPORTANT]
> This is a new feature that is released in V1.2.0 and shall work with Laravel 8.37 (with anonymous migration support). For verions below Laravel 8.37, this feature is disabled. You will need to update the **users** table migration and add the **sub** column (type:string, nullable:yes, index:yes).

If you need to overwrite the migrations that ship with AWS Cognito, you can publish them using the vendor:publish Artisan command:

```sh
php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider" --tag="migrations"
```

If you would like to prevent AWS Cognito's migrations from running entirely, you may use the ignoreMigrations method provided by AWS Cognito. Typically, this method should be called in the `register` method of your `AppServiceProvider` class. For example:

```php
use Ellaisys\Cognito\AwsCognito;

/**
 * Register any application services.
 */
public function register(): void
{
    AwsCognito::ignoreMigrations();
}
```

In case you are using the `ignoreMigrations` method, you will need to create your own migrations for updating the **users** table. Please ensure that you add the following columns to your **users** table:
- `sub` (type:string, nullable:yes, index:yes) - This column is used to store the Cognito user subject UUID. This column is used to map the Cognito user to the local user table. The default value of this column is `sub`. You can change this value by setting the `AWS_COGNITO_USER_SUBJECT_UUID` environment variable.

```env
AWS_COGNITO_USER_SUBJECT_UUID="sub"
```

### *Model Configurations*

This section provides guidance on how to configure your Application models to work with AWS Cognito.

#### User Model
---

The `User` model is the default model that is used by AWS Cognito to manage the users. In the default laravel setup, the `User` model is located in the `app/Models/User.php` file.

> [!IMPORTANT]
> Starting version 2.0.5 of this package, we have released a few traits to be included into your User Model.

These traits provide the necessary methods to manage the users in AWS Cognito. The `CognitoAuthenticatable` trait is located in the `Ellaisys\Cognito\Concerns` namespace. This trait references the `ManagesSubject`, `ManagesRegistration` and `ManagesPasskey` traits. These traits provide the necessary methods to manage the users in AWS Cognito.

As a basic laravel user, please ensure that you have included the `CognitoAuthenticatable` trait in your User model as shown below:

```php
namespace App\Models\Auth;
...
use Ellaisys\Cognito\Concerns\CognitoAuthenticatable;

class User extends Authenticatable
{
    ...
    use CognitoAuthenticatable;
    ...
}
```

For Advanced users, that want to use their own custom User model, you can set the `AWS_COGNITO_USER_MODEL` environment variable in your `.env` file. If plan to omit the `CognitoAuthenticatable` trait from your User model, you will need to manually implement the subject identifier column in your User model (`sub` by default). The sample code for the User model is provided below:

```php
namespace App\Models\Auth;
...
class User extends Authenticatable
{
    ...
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sub'
    ];
    ...
}

```


### *Session Storage Configurations*

The AWS Cognito service provider uses Laravel's built-in session management system to manage the session storage. By default, the session storage is set to `file` driver. You can change the session storage driver to `database`,  `dynamodb` in your `.env` file.

This package supports both `database` and `dynamodb` session storage drivers. You can choose the one that best suits your needs.

##### *DynamoDB Storage*
If you have a deployment architecture, that involves multiple servers and you want to maintain the Sessions or Tokens across the servers, you can use the AWS DynamoDB.

The library is capable of handling the DynamoDB with ease. All that you need to do is create the table in AWS DynamoDB and change a few configurations.

**Creating a new table in AWS DynamoDB**
1. Go to the AWS Console and create a new table.
2. Enter the *unique table name* as per your preferences.
3. The primary key (or partition key) should be `key` of type `string`
4. Use default settings and click the **Create** button
5. Update the DynamoDB table for the TTL columns as `expires_at` and set the TTL attribute to `enabled`. This will ensure that the expired sessions are automatically removed from the DynamoDB table.


**Update the .env file for Dynamo DB configurations**

Add/Edit the following fields to your `.env` file and set the values according to your AWS settings:

```env
# Cache Configuration
CACHE_DRIVER="dynamodb"
DYNAMODB_CACHE_TABLE="table-name-of-your-choice" //This should match the table name provided above

# Session Configuration
SESSION_DRIVER="dynamodb"
SESSION_LIFETIME=120
SESSION_DOMAIN="set-your-domain-name" //The domain name can be as per your preference
SESSION_SECURE_COOKIE=true

# DynamoDB Configuration
DYNAMODB_ENDPOINT="https://dynamodb.us-west-2.amazonaws.com" // You can change the endpoint based of different regions

```

Refer the [AWS DynamoDB Documentation](https://docs.aws.amazon.com/general/latest/gr/ddb.html) and refer the endpoints provided in **Service endpoints** section.


## References
- [AWS Cognito Documentation](https://docs.aws.amazon.com/cognito/latest/developerguide/what-is-amazon-cognito.html)
- [AWS DynamoDB Documentation](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/Introduction.html)
