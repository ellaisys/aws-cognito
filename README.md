<img src="https://www.ellaisys.com/wp-content/uploads/2020/11/eis_aws_cognito.png" width="100%" alt="EllaiSys AWS Cloud Capability"/>

# Laravel Package to manage Web and API authentication with AWS Cognito
AWS Cognito package using the AWS SDK for PHP

![Latest Version on Packagist](https://img.shields.io/packagist/v/ellaisys/aws-cognito?style=flat-square)
![Release Date](https://img.shields.io/github/release-date/ellaisys/aws-cognito?style=flat-square)
![Total Downloads](https://img.shields.io/packagist/dt/ellaisys/aws-cognito?style=flat-square)
![APM](https://img.shields.io/packagist/l/ellaisys/aws-cognito?style=flat-square)


This package provides a simple way to use AWS Cognito authentication in Laravel 7.x for Web and API Auth Drivers.
The idea of this package, and some of the code, is based on the package from Pod-Point which you can find here: [Pod-Point/laravel-cognito-auth](https://github.com/Pod-Point/laravel-cognito-auth), [black-bits/laravel-cognito-auth](https://github.com/black-bits/laravel-cognito-auth) and [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth).

We decided to use it and contribute it to the community as a package, that encourages standarised use and a RAD tool for authentication using AWS Cognito. 

Currently we have the following features implemented in our package:

- Registration and Confirmation E-Mail
- Forced password change at first login (configurable)
- Login
- Remember Me Cookie
- Single Sign On
- Forgot Password
- User Deletion
- Edit User Attributes
- Reset User Password
- Confirm Sign Up
- Easy API Token handling (uses the cache driver)
- DynamoDB support for Web Sessions and API Tokens (useful for server redundency OR multiple containers)

### Disclaimer
_This package is currently in production ready mode with already a few implementations done. We would be happy to hear from you, about the defects or new feature enhancements. However, this being a free support, we would not be able to commit to support SLAs or timelines._

## Installation

You can install the package via composer.

```bash
composer require ellaisys/aws-cognito
```

#### Laravel 5.4 and before
Using a version prior to Laravel 5.5 you need to manually register the service provider.

```php
    // config/app.php
    'providers' => [
        ...
        Ellaisys\Cognito\Providers\AwsCognitoServiceProvider::class,
        
    ];
```

Next you can publish the config and the view.

```bash
    php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider"
```
Last but not least you want to change the auth driver. To do so got to your config\auth.php file and change it 
to look the following:

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

## Cognito User Pool

In order to use AWS Cognito as authentication provider, you require a Cognito User Pool. 

If you haven't created one already, go to your [Amazon management console](https://console.aws.amazon.com/cognito/home) and create a new user pool. 

Next, generate an App Client. This will give you the App client id and the App client secret
you need for your `.env` file. 

*IMPORTANT: Don't forget to activate the checkbox to Enable sign-in API for server-based Authentication. 
The Auth Flow is called: ADMIN_USER_PASSWORD_AUTH (formerly ADMIN_NO_SRP_AUTH)*

You also need a new IAM Role with the following Access Rights:

- AmazonCognitoDeveloperAuthenticatedIdentities
- AmazonCognitoPowerUser
- AmazonESCognitoAccess

From this user you can fetch the AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY.

### Cognito API configuration

Add the following fields to your `.env` file and set the values according to your AWS settings:

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
For more details on how to find AWS_COGNITO_CLIENT_ID, AWS_COGNITO_CLIENT_SECRET and AWS_COGNITO_USER_POOL_ID for your application, please refer [COGNITOCONFIG File](COGNITOCONFIG.md)

### Importing existing users into the Cognito Pool

If you are already working on an existing project and want to integrate Cognito you have to [import a user csv file to your Cognito Pool](https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-user-pools-using-import-tool.html).

## Usage

Our package is providing you 5 traits you can just add to your Auth Controllers to get our package running.

- Ellaisys\Cognito\Auth\AuthenticatesUsers
- Ellaisys\Cognito\Auth\RegistersUsers
- Ellaisys\Cognito\Auth\ResetsPasswords
- Ellaisys\Cognito\Auth\SendsPasswordResetEmails
- Ellaisys\Cognito\Auth\VerifiesEmails


In the simplest way you just go through your Auth Controllers and change namespaces from the traits which are currently implemented from Laravel.

You can change structure to suit your needs. Please be aware of the @extend statement in the blade file to fit into your project structure. 
At the current state you need to have those 4 form fields defined in here. Those are `token`, `email`, `password`, `password_confirmation`. 

## Single Sign-On

With our package and AWS Cognito we provide you a simple way to use Single Sign-Ons. 
For configuration options take a look at the config [cognito.php](/config/cognito.php).


When you want SSO enabled and a user tries to login into your application, the package checks if the user exists in your AWS Cognito pool. If the user exists, he will be created automatically in your database provided the `add_missing_local_user_sso` is to `true`, and is logged in simultaneously.

That's what we use the fields `sso_user_model` and `cognito_user_fields` for. In `sso_user_model` you define the class of your user model. In most cases this will simply be _App\User_. 

With `cognito_user_fields` you can define the fields which should be stored in Cognito. Put attention here. If you define a field which you do not send with the Register Request this will throw you an InvalidUserFieldException and you won't be able to register. 

Now that you have registered your users with their attributes in the AWS Cognito pool and your database and you want to attach a second app which should use the same pool. Well, that's actually pretty easy. You can use the API provisions that allows multiple projects to consume the same AWS Cognito pool. 

*IMPORTANT: if your users table has a password field you are not going to need this anymore. What you want to do is set this field to be nullable, so that users can be created without passwords. From now on, Passwords are stored in Cognito.

Any additional registration data you have, for example `firstname`, `lastname` needs to be added in 
[cognito.php](/config/cognito.php) cognito_user_fields config to be pushed to Cognito. Otherwise they are only stored locally 
and are not available if you want to use Single Sign On's.*


## Middleware configuration for API Routes
In case you are using this library as API driver, you can register the middleware into the kernal.php in the $routeMiddleware

```php

    protected $routeMiddleware = [
        ...
        'aws-cognito' => \Ellaisys\Cognito\Http\Middleware\AwsCognitoAuthenticate::class
    ]

```

To use the middleware into the **Web routes**, you can use the std auth middleware as shown below

```php

    Route::middleware('auth')->get('user', 'NameOfTheController@functionName');

```

To use the middleware into the **API routes**, as shown below

```php

    Route::middleware('aws-cognito')->get('user', 'NameOfTheController@functionName');

```


## Registering Users 

As a default, if you are registering a new user with Cognito, Cognito will send you an email during signUp that includes the username and temporary password for the users to verify themselves.

Using this library in conjunction with **AWS Lambda**, once can look to customize the email template and content. The email template can be text or html based. The Lambda code for not included in this code repository. You can create your own. Any object (array) that you pass to the registration method is transferred as is to the lambda function, we are not prescriptive about the attribute names.

We have made is very easy for anyone to use the default behaviour. 

1. You don't need to create an extra field to store the verification token.
2. You don't have to bother about the Sessions or API tokens, they are managed for you. The session or token is managed via the standard mechanism of Laravel. You have the liberty to keep it where ever you want, no security loop holes.
3. If you use the trait provided by us 'Ellaisys\Cognito\Auth\RegistersUsers', the code will be limited to just a few lines
4. if you are using the Laravel scafolding, then make the password nullable in DB or drop it from schema. Passwords will be only managed by AWS Cognito.

```php
    use Ellaisys\Cognito\Auth\RegistersUsers;

    class UserController extends BaseController
    {
        use RegistersUsers;

        public function register(Request $request)
        {
            $validator = $request->validate([
                'name' => 'required|max:255',
                'email' => 'required|email|max:64|unique:users',
                'password' => 'sometimes|confirmed|min:6|max:64',
            ]);

            //Create credentials object
            $collection = collect($request->all());
            $data = $collection->only('name', 'email', 'password'); //passing 'password' is optional.

            //Register User in cognito
            if ($cognitoRegistered=$this->createCognitoUser($data)) {

                //If successful, create the user in local db
                User::create($collection->only('name', 'email'));
            } //End if

            //Redirect to view
            return view('login');
        }
    }

```

5. You don't need to turn off Cognito to send you emails. We rather propose the use of AWS Cognito or AWS SMS mailers, such that use credentials are always secure.

## User Authentication

We have provided you with a useful trait that make the authentication very simple (with Web or API routes). You don't have to worry about any additional code to manage sessions and token (for API).

The trait takes in some additional parameters, refer below the function signature of the trait. Note that the function takes the object of **Illuminate\Support\Collection** instead of **Illuminate\Http\Request**. This will allow you to use this function in any tier of the code.

Also, the 'guard' name reference is passed, so that you can reuse the function for multiple guard drivers in your project. The function has the capability to handle the Session and Token Guards with multiple drivers and providers as defined in /config/auth.php

```php

    namespace Ellaisys\Cognito\Auth;

    protected function attemptLogin (
        Collection $request, string $guard='web', 
        string $paramUsername='email', string $paramPassword='password', 
        bool $isJsonResponse=false
    ) {
        ...
        ...


        ...
    }

```

In case you want to use this trait for Web login, you can write the code as shown below in the AuthController.php

```php

    namespace App\Http\Controllers;

    ...

    use Ellaisys\Cognito\AwsCognitoClaim;
    use Ellaisys\Cognito\Auth\AuthenticatesUsers as CognitoAuthenticatesUsers;

    class AuthController extends Controller
    {
        use CognitoAuthenticatesUsers;

        /**
         * Authenticate User
         * 
         * @throws \HttpException
         * 
         * @return mixed
         */
        public function login(\Illuminate\Http\Request $request)
        {
            ...

            //Convert request to collection
            $collection = collect($request->all());

            //Authenticate with Cognito Package Trait (with 'web' as the auth guard)
            if ($response = $this->attemptLogin($collection, 'web')) {
                if ($response===true) {
                    return redirect(route('home'))->with('success', true);
                } else if ($response===false) {
                    // If the login attempt was unsuccessful you may increment the number of attempts
                    // to login and redirect the user back to the login form. Of course, when this
                    // user surpasses their maximum number of attempts they will get locked out.
                    //
                    //$this->incrementLoginAttempts($request);
                    //
                    //$this->sendFailedLoginResponse($collection, null);
                } else {
                    return $response;
                } //End if
            } //End if

        } //Function ends

        ...
    } //Class ends

```

In case you want to use this trait for API based login, you can write the code as shown below in the AuthApiController.php

```php

    namespace App\Api\Controller;

    ...

    use Ellaisys\Cognito\AwsCognitoClaim;
    use Ellaisys\Cognito\Auth\AuthenticatesUsers as CognitoAuthenticatesUsers;

    class AuthApiController extends Controller
    {
        use CognitoAuthenticatesUsers;

        /**
         * Authenticate User
         * 
         * @throws \HttpException
         * 
         * @return mixed
         */
        public function login(\Illuminate\Http\Request $request)
        {
            ...

            //Convert request to collection
            $collection = collect($request->all());

            //Authenticate with Cognito Package Trait (with 'api' as the auth guard)
            if ($claim = $this->attemptLogin($collection, 'api', 'username', 'password', true)) {
                if ($claim instanceof AwsCognitoClaim) {
                    return $claim->getData();
                } else {
                    return response()->json(['status' => 'error', 'message' => $claim], 400);
                } //End if
            } //End if

        } //Function ends


        ...
    } //Class ends

```

## Delete User

If you want to give your users the ability to delete themselves from your app you can use our deleteUser function
from the CognitoClient. 

To delete the user you should call deleteUser and pass the email of the user as a parameter to it.
After the user has been deleted in your cognito pool, delete your user from your database too.

```php
        $cognitoClient->deleteUser($user->email);
        $user->delete();
```

We have implemented a new config option `delete_user`, which you can access through `AWS_COGNITO_DELETE_USER` env var. 
If you set this config to true, the user is deleted in the Cognito pool. If it is set to false, it will stay registered. 
Per default this option is set to false. If you want this behaviour you should set USE_SSO to true to let the user 
restore themselves after a successful login.

To access our CognitoClient you can simply pass it as a parameter to your Controller Action where you want to perform 
the deletion. 

```php
    public function deleteUser(Request $request, AwsCognitoClient $client)
```

Laravel will take care of the dependency injection by itself. 

```
    IMPORTANT: You want to secure this action by maybe security questions, a second delete password or by confirming 
    the email address.
```

## Storing Web Sessions or API Tokens in DynamoDB (Useful for multiserver/container implementation)

If you have a deployment architecture, that involves multiple servers and you want to maintain the web sessions or API tokens across the servers, you can use the AWS DynamoDB. The library is capable of handling the DynamoDB with ease. All that you need to do is create the table in AWS DynamoDB and change a few configurations.

### Creating a new table in AWS DynamoDB
1. Go to the AWS Console and create a new table. 
2. Enter the unique table name as per your preferences.
3. The primary key (or partition key) should be **key** of type **string**
4. Use default settings and click the **Create** button

### Update the .env file for Dynamo DB configurations
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

Update the DynamoDB table for the TTL columns as **expires_at**


## Automatic User Password update for API usage (for New Cognito Users)

In case of the new cognito users, the AWS SDK will send a session key and the user is expected to change the password, in a forced mode. Make sure you force the users to change the password for the first login by new cognito user.

However, if you have an API based implementation, and want to automatically authenticate the user without forcing the password change, you may do that with below setting fields to your `.env` file

```php

    AWS_COGNITO_FORCE_PASSWORD_CHANGE_API=false     //Make true for forcing password change
    AWS_COGNITO_FORCE_PASSWORD_AUTO_UPDATE_API=true //Make false for stopping auto password change

```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security related issues, please email [support@ellaisys.com](mailto:support@ellaisys.com) and also add it to the issue tracker.

## Credits

- [EllaiSys Team](https://github.com/ellaisys)
- [Amit Dhongde](https://github.com/amitdhongde)

## Support us

EllaiSys is a web and consulting agency specialized in Cloud Computing (AWS and Azure), DevOps, and Product Engneering. We specialize into LAMP and Microsoft stack development. You'll find an overview of what we do [on our website](https://ellaisys.com).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
