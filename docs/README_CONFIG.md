# **Configurations**

This document provides guidance on configuring the AWS Cognito service and the Laravel application to work with AWS Cognito. It is intended for developers who are familiar with Laravel and AWS services.

> [!NOTE]
> Updated On 2026-07-10


## **Contents**
- [AWS Configurations](COGNITOCONFIG.md)
    + [AWS IAM configuration](COGNITOCONFIG.md#aws-iam-configuration)
    + [AWS Cognito configuration](COGNITOCONFIG.md#aws-cognito-configuration)
- [Laravel Configurations](#laravel-configurations)
    + [ServiceProvider Registration](#serviceprovider-registration)
    + [Environment Variables](#environment-variables)
    + [Changes in Auth Configurations](#changes-in-auth-configurations)
    + [Publishing Configurations](#publishing-configurations)
    + [Database Configurations](#database-configurations)
    + [Session Storage Configurations](#session-storage-configurations)
        * DynamoDB Storage
- [References](#references)

## **AWS Configurations**

The AWS configurations are required to be set up in order to use the AWS Cognito service. The detailed steps for setting up the AWS Cognito service are provided in the [AWS Configurations](COGNITOCONFIG.md) document. Please refer to that document for detailed instructions on how to set up the AWS Cognito service.


## **Laravel Configurations**

### *ServiceProvider Registration*
---

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


### *Environment Variables*
---

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


### *Changes in Auth Configurations*
---

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
---

You can publish the AWS Cognito configuration file using the following command:
```sh
php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider" --tag="config"
```


### *Database Configurations*
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


### *Session Storage Configurations*
---

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

```php
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


## **References**
- [AWS Cognito Documentation](https://docs.aws.amazon.com/cognito/latest/developerguide/what-is-amazon-cognito.html)
- [AWS DynamoDB Documentation](https://docs.aws.amazon.com/amazondynamodb/latest/developerguide/Introduction.html)
