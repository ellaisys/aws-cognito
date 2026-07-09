# **Configurations**

## **Contents**
- [AWS Configurations](#aws-configurations)
    + [AWS IAM configuration](#aws-iam-configuration)
    + [AWS Cognito configuration](#aws-cognito-configuration)
- [Laravel Configurations](#laravel-configurations)
    + [ServiceProvider Registration](#serviceprovider-registration)
    + [Environment Variables](#environment-variables)
    + [Publishing Configurations](#publishing-configurations)
- [Database Configurations](#database-configurations)

### AWS Configurations
---

#### *AWS IAM configuration*

You also need a new `IAM Role` with the following Access Rights:

- AmazonCognitoDeveloperAuthenticatedIdentities
- AmazonCognitoPowerUser
- AmazonESCognitoAccess

From this IAM User you must use the **AWS_ACCESS_KEY_ID** and **AWS_SECRET_ACCESS_KEY** in the laravel environment file.

#### *AWS Cognito configuration*


### Laravel Configurations
---

#### *ServiceProvider Registration*

*<u>Laravel 5.4 and before</u>*</br>
Using a version prior to Laravel 5.5 you need to manually register the service provider in your `bootstrap/app.php` file:

```php
// bootstrap/app.php
'providers' => [
    ...
    Ellaisys\Cognito\Providers\AwsCognitoServiceProvider::class,
];
```

*<u>Laravel 11.0 and above</u>*</br>
With Laravel versions 11.0 and above the middleware congiguration is defined in the `bootstrap/app.php` file. Please configure as shown below

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

#### *Environment Variables*
In order to use AWS Cognito, you will need to add the following minimum configurations to your Laravel application. You can do this by adding the following fields to your `.env` file:

```php
# AWS configurations for cloud storage
AWS_ACCESS_KEY_ID="Axxxxxxxxxxxxxxxxxxxxxxxx6"
AWS_SECRET_ACCESS_KEY="mxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx+"

# AWS Cognito configurations
AWS_COGNITO_CLIENT_ID="6xxxxxxxxxxxxxxxxxxxxxxxxr"
AWS_COGNITO_CLIENT_SECRET="1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx1"
AWS_COGNITO_USER_POOL_ID="xxxxxxxxxxxxxxxxx"
AWS_COGNITO_REGION="xxxxxxxxxxx" //optional - default value is 'us-east-1'
AWS_COGNITO_VERSION="latest" //optional - default value is 'latest'
```

For more details on how to find `AWS_COGNITO_CLIENT_ID`, `AWS_COGNITO_CLIENT_SECRET` and `AWS_COGNITO_USER_POOL_ID` for your application, please refer to the [AWS Configurations](#aws-configurations).

> [!NOTE]
> To sync the web session timeout with the cognito access token ttl value, set the `SESSION_LIFETIME` parameter in the .env file. This value is in minutes with the default value being 120 mins i.e. 2 hours. This will ensure that the laravel session times out at the same time as the access token.
---

#### *Changes in Auth Configurations*
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

#### *Publishing Configurations* (Optional)
You can publish the AWS Cognito configuration file using the following command:
```sh
php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider" --tag="config"
```

### Database Configurations
---
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
