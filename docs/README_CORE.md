# **AWS Cognito Core Functionality**

## **Contents**
- [Introduction](#introduction)
- [Configurations](#configurations)
- [Features](#features)
- Quick Start
    - [Blade Component](#blade-component-web-app)
- Advanced Topics
    - [API Documentation](#api-documentation)
    - [API Routes](#api-routes)
- [References](#references)


## **Introduction**


## **Configurations**


## **Features**

- [Registration and Confirmation E-Mail (Sign Up)](#registering-users)
- Forced password change at first login (configurable)
- [Login (Sign In)](#user-authentication)
- Token Validation for all Session and Token Guard Requests
- Remember Me Cookie
- Single Sign On (Fix: Issue #86)
- Forgot Password (Resend - configurable)
- User Deletion
- Edit User Attributes
- Reset User Password
- Confirm Sign Up
- Easy API Token handling (uses the cache driver)
- [DynamoDB support for Web Sessions and API Tokens (useful for server redundency OR multiple containers)](#storing-web-sessions-or-api-tokens-in-dynamodb-useful-for-multiservercontainer-implementation)
- Easy configuration of Token Expiry (Manage using the cognito console, no code or configurations needed)
- Support for App Client without Secret
- Support for Cognito Groups, including assigning a default group to a new user
- Session (Web) now has AccessToken and RefreshToken as part of the claim object
- [Refresh Token API](#refresh-token)
- [Logout (Sign Out) - Remove access tokens from AWS](#signout-remove-access-token)
- [Forced Logout (Sign Out) - Revoke the RefreshToken from AWS](#signout-remove-access-token)
- [MFA Implementation for Session and Token Guards](./docs/README_MFA.md) **Updated**
- [Password validation based on Cognito Configuration](#password-validation-based-of-cognito-configuration)
- [Mapping Cognito User using Subject UUID](#mapping-cognito-user-using-subject-uuid)
- [Preconfigured routes and controllers for Web and API ](./docs/README_ROUTES.md#routes)
- [Preconfigured views for Web ](./docs/README_ROUTES.md#web-views-and-components)
- [FIDO2 Security Keys Passkey](./docs/README_FIDO2.md) **Updated**
- [SRP Authentication](./docs/README_SRP.md) **New Feature**
- [Device Authentication](./docs/README_DEVICE_AUTH.md) **New Feature**


## **Registering Users OR Sign Up**

The registration process is now simplified and you can use the trait `RegistersUsers` provided by us. The trait has the capability to handle both the registration types, `invite` and `register`. The default type is set to **register**. You can change the behaviour of the register method by setting following configuration.

```php
AWS_COGNITO_REGISTRATION_TYPE="invite" //optional - default is register
```

You will need to configure the AWS Cognito User Pool to allow [Self Registration](COGNITOCONFIG.md#step-11-sign-up-settings). If this is not enabled, then the users will have to be created by an administrator by inviting them to the application.

 Refer to the AWS Cognito documentation for more details on how to enable self registration.

As a default, if you are registering a new user with Cognito, Cognito will send you an email during signUp that includes the username and temporary password for the users to verify themselves.

Using this library in conjunction with **AWS Lambda**, once can look to customize the email template and content. The email template can be text or html based content. The Lambda code is not included in this code repository.

We have made is very easy for anyone to use the default behaviour.

3. If you use the trait provided by us 'Ellaisys\Cognito\Auth\RegistersUsers', the code will be limited to just a few lines
4. if you are using the Laravel scafolding, then make the password nullable in DB or drop it from schema. Passwords will be only managed by AWS Cognito.

```php
use Ellaisys\Cognito\Auth\RegistersUsers;

class RegisterController extends Controller
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

5. You don't need to turn off Cognito to send you emails. We rather propose the use of AWS Cognito or AWS SMS mailers, such that user credentials are always secure.

6. In case you want to suppress the mails to be sent to the new users, you can configure the parameter given below to skip welcome mails to new user registration. Default configuration shall send the welcome email.

```php
AWS_COGNITO_NEW_USER_MESSAGE_ACTION="SUPPRESS"
```

7. The configuration given below allows the new user's email address to be auto marked as verified.

```php
AWS_COGNITO_FORCE_NEW_USER_EMAIL_VERIFIED=true //optional - default value is false.
```

8. To assign a default group to a new user when registering set a name of the user group as per the configuration done via AWS Cognito Management Console. The default value is set to null.

```php
AWS_COGNITO_DEFAULT_USER_GROUP="Customers"
```

9. To enable custom password or user defined password, the below configuration if set to **true** will force the user to set the password during registration, else cognito will generate a random password and send over email and/or SMS based on the configurations.

```php
AWS_COGNITO_FORCE_NEW_USER_PASSWORD=true //optional - default value is false.  
```

10. The registration process now allows two types of request, 'invite' and 'register'. The register is self registration and an verification email is sent to the user. The invite is sent from the admin and contains the temporary cedentials. The RegistersUsers Trait allows two methods invite and register respectively. The default method called in the trait is set to **register**. You can change the behaviour of the register method by setting following configuration.

```php
    AWS_COGNITO_REGISTRATION_TYPE="register" //optional - the default type is invite
```


## **User Authentication OR Sign In**

We have provided you with a useful trait that make the authentication very simple (with Web or API routes). You don't have to worry about any additional code to manage sessions and token (for API).

> [!NOTE]
> The Access Token is now validated with the AWS Cognito certificate. If the certificate is incorrect or expired, it will throw am exception.

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
            } elseif ($response===false) {
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

## **Log Out OR Signout (Remove Access Token)**

The logout methods are now part of the guard implementations, the logout method removes the access-tokens from AWS and also removes from Application Storage managed by this library. Just calling the auth guard logout method will be sufficient. You can implement it into the routes or controller based on your development preference.

The logout method now takes an **optional** boolean parameter (true) to revoke RefreshToken. The default value is (false) and that will persist the Refresh Token with AWS Cognito.

```php
...
Auth::guard('api')->logout();

...
Auth::guard('api')->logout(true); //Revoke the Refresh Token.
```


## **Refresh Token**

You can use this trait for API to generate new token

```php
namespace App\Api\Controller;

...
use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\RefreshToken;

class AuthApiController extends Controller
{
    use RefreshToken;

    /**
     * Generate a new token using refresh token.
     * 
     * @throws \HttpException
     * 
     * @return mixed
     */
    public function refreshToken(\Illuminate\Http\Request $request)
    {
        ...
        $validator = $request->validate([
            'email' => 'required|email',
            'refresh_token' => 'required'
        ]);
        
        try {
            return $this->refresh($request, 'email', 'refresh_token');
        } catch (Exception $e) {
            return $e;
        }
    } //Function ends
    ...
} //Class ends
```


## **Delete User**

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


## **Forgot Password**

In case the user has not activated the account, AWS Cognito as a default feature does not allow user of use the forgot password feature. We have introduced the AWS documented feature that allows the password to be resent.

We have made this configurable for the developers so that they can use it as per the business requirement. The configuration takes a boolean value. Default is true (allows resend of forgot password)

```php
AWS_COGNITO_ALLOW_FORGOT_PASSWORD_RESEND=true
```





























## Usage
Our package is providing you 10 traits you can just add to your Auth Controllers to get our package running.

- Ellaisys\Cognito\Auth\AuthenticatesUsers
- Ellaisys\Cognito\Auth\ConfirmsPasswords
- Ellaisys\Cognito\Auth\RefreshToken
- Ellaisys\Cognito\Auth\RegisterMFA
- Ellaisys\Cognito\Auth\RegistersUsers
- Ellaisys\Cognito\Auth\ResetsPasswords
- Ellaisys\Cognito\Auth\SendsPasswordResetEmails
- Ellaisys\Cognito\Auth\VerifiesEmails
- Ellaisys\Cognito\Auth\WebAuthPasskey

In the simplest way you just go through your Auth Controllers and use these traits which are currently implemented in Laravel. The Controllers are now also provided and preconfigured with the traits. You can use them as they are or change them to fit your needs.

You can change structure to suit your needs. Please be aware of the @extend statement in the blade file to fit into your project structure.


## Single Sign-On

With our package and AWS Cognito we provide you a simple way to use Single Sign-Ons.
For configuration options take a look at the config [cognito.php](/config/cognito.php).

When you want SSO enabled and a user tries to login into your application, the package checks if the user exists in your AWS Cognito pool. If the user exists, he will be created automatically in your database provided the `add_missing_local_user` is to `true`, and is logged in simultaneously.

That's what we use the fields `sso_user_model` and `cognito_user_fields` for. In `sso_user_model` you define the class of your user model. In most cases this will simply be _App\Models\User_.

With `cognito_user_fields` you can define the fields which should be stored in Cognito. Put attention here. If you define a field which you do not send with the Register Request this will throw you an InvalidUserFieldException and you won't be able to register.

Now that you have registered your users with their attributes in the AWS Cognito pool and your database and you want to attach a second app which should use the same pool. Well, that's actually pretty easy. You can use the API provisions that allows multiple projects to consume the same AWS Cognito pool.

*IMPORTANT: if your users table has a password field you are not going to need this anymore. What you want to do is set this field to be nullable, so that users can be created without passwords. From now on, Passwords are stored in Cognito.

Any additional registration data you have, for example `firstname`, `lastname` needs to be added in
[cognito.php](/config/cognito.php) cognito_user_fields config to be pushed to Cognito. Otherwise they are only stored locally
and are not available if you want to use Single Sign On's.*






## Automatic User Password update for API usage (for New Cognito Users)

In case of the new cognito users, the AWS SDK will send a session key and the user is expected to change the password, in a forced mode. Make sure you force the users to change the password for the first login by new cognito user.

However, if you have an API based implementation, and want to automatically authenticate the user without forcing the password change, you may do that with below setting fields to your `.env` file

```php
AWS_COGNITO_FORCE_PASSWORD_CHANGE_API=false     //Make true for forcing password change
AWS_COGNITO_FORCE_PASSWORD_AUTO_UPDATE_API=true //Make false for stopping auto password change
```

## Support for App Client without Secret enabled

The library now supports where the AWS configuration of App Client with the Client Secret set to disabled. Use the below configuration into the environment file to enable/disable this. The default is marked as enable (i.e. we expect the App Client Secret to be enabled in AWS Cognito configuration)

```php
AWS_COGNITO_CLIENT_SECRET_ALLOW=false
```

## Password Validation based of Cognito Configuration

This library fetches the password policy from the cognito pool configurations. The laravel request validations are done based on the regular expression that is created based on this policy. This validations are performed during the Sign Up (Registation), Sign In (Login), Reset and Change password based flows. The validation messages for the password are also dynamic in nature and change based on the configurations.

>[!IMPORTANT]
>In case of special characters, we are supporting all except the pipe character **|** for now.
>We are working on making sure that pipe character is handled soon.

