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
- [AWS Configurations](#aws-configurations)
- [Laravel Configurations](#laravel-configurations)


### *AWS Configurations*
---


### *Laravel Configurations*
---

The package allows some configurations, which can be set in the .env file. The default values are set in the configuration file. You can change the default values by setting the keys in the .env file.

```env
# Configuration for user group assignment for registered or invited users.
AWS_COGNITO_DEFAULT_USER_GROUP="Customers" //optional - default value is null.


AWS_COGNITO_FORCE_NEW_USER_PASSWORD=true //optional - default value is false.

# Configuration for user invitation only
AWS_COGNITO_NEW_USER_MESSAGE_ACTION="SUPPRESS" //optional - default value is null.
AWS_COGNITO_FORCE_NEW_USER_EMAIL_VERIFIED=true //optional - default value is false.
```

## **Features**

- [Registering Users OR Sign Up](#registering-users-or-sign-up)
    + [Self Registration](#self-registration)
    + [Verification of User](#verification-of-user)

- [User Invitation OR Invite User](#user-invitation-or-invite-user)
- [User Authentication OR Sign In](#user-authentication-or-sign-in)
- [Log Out OR Sign out](#log-out-or-sign-out)
- [Refresh Token](#refresh-token)
- [Delete User](#delete-user)


## **Registering Users OR Sign Up**

The registration process is simplified into just two steps, self registration and verification. They are detailed below in detail. The overall process is now simplified to just a couple of steps. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

To enable user defined password to be set during registration or invitation, the key `AWS_COGNITO_FORCE_NEW_USER_PASSWORD` in the environment setting should be set to **true** as shown in the [Laravel Configurations](#laravel-configurations) section. This forces the user to set the password during registration, else cognito will generate a random password and send over email and/or SMS based on the configurations.

This package also supports the AWS Cognito Groups. You can assign a default group to a new user when registering or inviting. This can be configured in the environment file. Use the key `AWS_COGNITO_DEFAULT_USER_GROUP` to set the default group name. The group name should be as per the configuration done via AWS Cognito Management Console. The default value is set to null. 


### *Self Registration*
---

The package provides you with a trait that makes the registration process very simple. The package provides a trait `RegistersUsers` that you can add to your controller to make the registration process functional. The namespace for the trait is `Ellaisys\Cognito\Auth\RegistersUsers`. The trait has the capability to handle the following registration types:
    - `register` (self registration), and
    - `invite` (admin invited registration).

You will need to configure the AWS Cognito User Pool to allow [Self Registration](COGNITOCONFIG.md#step-11-sign-up-settings). If this is not enabled, then the users will have to be created by an administrator by inviting them to the application.

After the user is successfully registered, the status of the user is `UNCONFIRMED` and email is `UNVERIFIED`. A verification email is sent to the user's email and/or phone number.

The package provides routes and controllers for the registration process. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

```php
//Route to register a new user
use Ellaisys\Cognito\Http\Controllers\Auth\RegisterController;
...

Route::group(['prefix' => 'register'], function() {
    Route::get('/',  function () { return view('cognito::pages.auth.registers.register'); })->name('form.register');
    Route::post('/', [RegisterController::class, 'actionRegister'])->name('action.register.submit');
});
```

#### Advanced Registration Options

In case you want to customize the registration process, and write your own controller, you can use the `register` method provided by the trait. The method takes a collection of user data and creates a new user in the AWS Cognito User Pool. The method returns user object on successful registration.

```php
use Ellaisys\Cognito\Auth\RegistersUsers;

class RegisterController extends Controller
{
    use RegistersUsers;
    protected $clientMetadata = null;

    public function __construct()
    {
        $this->middleware('guest')->except(['actionInvite', 'invite']);

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(true);

        parent::__construct();
    } //Function ends

    public function actionRegister(\Illuminate\Http\Request $request)
    {
        ...
        //Get Registered User
        $user = $this->register($request, $this->clientMetadata);
    }
}
```

The trait triggers `PreRegistrationEvent` and `PostRegistrationEvent` events before and after the registration process. You can listen to these events and perform any additional actions as per your business requirement.


### *Verification of User*
---

The verification of the user is handled by the `VerifiesEmails` trait. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

The trait has two key methods, `verify` and `resend`. As the name suggests, the verify method is used to verify the users email and/or phone. The resend method is to be called incase the user has not received the email or the code/link has expired. The resend method will send a new verification link to the user.

After the user is successfully verified, the status of the user is `CONFIRMED` and email is `VERIFIED`.

The routes for the verification process are provided by us. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

```php
//Route to verify a new user
use Ellaisys\Cognito\Http\Controllers\Auth\VerificationController;
...

Route::group(['prefix' => 'register/verify'], function() {
    Route::get('/',  function () { return view('cognito::pages.auth.registers.verify'); })->name('form.register.verify');
    Route::post('/', [VerificationController::class, 'verify'])->name('action.register.verify');

    Route::group(['prefix' => 'resend-code'], function() {
        Route::get('/',  function () { return view('cognito::pages.auth.registers.resend'); })->name('form.register.resend_code');
        Route::post('/', [VerificationController::class, 'resend'])->name('action.register.resend_code');
    });
});
```


## **User Invitation OR Invite User**

A new user can be invited by an administrator into the application. The invitation process is simplified into simple steps, `invite` and `verification`. However, you can also auto-verify the user. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

In case you want to suppress the invitation mail sent to the new users, set the environment variable `AWS_COGNITO_NEW_USER_MESSAGE_ACTION` to **SUPPRESS**. You can configure the parameter given below to skip welcome mails to new user registration. Default configuration shall send the welcome email.

Similarly, you can also auto-verify the new user by setting the environment variable `AWS_COGNITO_FORCE_NEW_USER_EMAIL_VERIFIED` to **true**. This will mark the new user's email address as verified. Default configuration shall not mark the email address as verified and the user will have to verify the email address by clicking on the link sent to the email address.













## **User Authentication OR Sign In**

Basic password based user authentication is simplified into just one step, the login. It is essential that the `ALLOW_USER_PASSWORD_AUTH` is enabled in the AWS Cognito User Pool. For details on how to enable this, please refer to the [AWS Cognito Configuration - App Client Settings](COGNITOCONFIG.md#step-6-edit-app-client-settings) section.

Other authentication types like SRP, MFA, Passkeys, etc. are also supported by the package. In case you want to know more about these authentication types, please refer to the [AWS Cognito Configuration - App Client Settings](COGNITOCONFIG.md#step-6-edit-app-client-settings) section, OR  you can refer the features section of this document for more details.

The package provides you with a trait that makes the authentication process very simple. The package provides a trait `AuthenticatesUsers` that you can add to your controller to make the authentication process functional. The namespace for the trait is `Ellaisys\Cognito\Auth\AuthenticatesUsers`. The trait has the capability to handle the following authentication types:
    - `attemptLogin` (Login with username and password), and
    - `attemptLoginSRP` (Login with SRP)
    - `challenge` (Login with challenge e.g. MFA, Passkeys, etc.)

You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes. The package provides routes and controllers for the authentication process.

```php
//Route to authenticate a user

use Ellaisys\Cognito\Http\Controllers\Auth\LoginController;
...
Route::post('/login', [LoginController::class, 'login']);
Route::post('/login/srp', [LoginController::class, 'loginSRP']);
Route::post('/login/challenge', [LoginController::class, 'actionChallenge']);
```

The trait triggers `PreAuthEvent`, `PostAuthSuccessEvent` and `PostAuthFailedEvent` events before and after the login process. You can listen to these events and perform any additional actions as per your business requirement.


#### Advanced Authentication Options

For advanced authentication options, you can use the `attemptLogin` method provided by the trait. The method takes a collection of user data and authenticates the user in the AWS Cognito User Pool. The method returns a claim object on successful authentication. The credential object in the request should contain the `username` and `password` parameters for Basic Authentication. The method also takes an optional parameter to specify the authentication flow type. The default value is `USER_PASSWORD_AUTH`.

```php
use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
...

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except(['logout']);
        $this->setIsControllerAction(false); //Set flag to indicate action called from controller
        parent::__construct();
    } //Function ends

    public function login(\Illuminate\Http\Request $request)
    {
        ...
        $claim = $this->attemptLogin($request, CognitoAuthFlowTypes::USER_PASSWORD_AUTH);

        if ($claim instanceof AwsCognitoClaim) {
            //Authentication successful
            ...
        } else {
            // Challenge generated, handle the challenge
            ...
        } //End if

    } //Function ends
}
```


## **Log Out OR Sign out**

The package provides you with a trait that makes the logout process very simple. The package provides a trait `AuthenticatesUsers` that you can add to your controller to make the logout process functional. The namespace for the trait is `Ellaisys\Cognito\Auth\AuthenticatesUsers`. The trait has the capability to handle the following logout types:
    - `logout` (Logout, but persists the refresh token), and
    - `logout(true)` (Logout, and revoke the refresh token)

In multiple application scenarios, you may want to logout the user from one application then use the `logout()` method to persist the refresh token. This will allow the user to maintain the session in other applications. In case you want to logout the user from all applications, you can use the `logout(true)` method to revoke the refresh token. This will require the user to authenticate again in all applications. This is useful in Single Sign-On scenarios where you want to logout the user from all applications. This is detailed in the [Single Sign-On](#single-sign-on) section.

If you are using the routes provided by us, you can use the preconfigured controller with following routes. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

```php
//Route to logout a user, secure the route with auth middleware
use Ellaisys\Cognito\Http\Controllers\Auth\LoginController;
...
Route::post('/logout', [LoginController::class, 'logout']);
Route::post('/logout/forced', [LoginController::class, 'logoutForced']);
```

The trait triggers a `PreLogoutEvent` and `PostLogoutEvent` during the logout process. You can listen to these events and perform any additional actions as per your business requirement.

#### Advanced Logout Options

For advanced logout options, you can use the `logout` method provided by the trait. The method takes a boolean parameter to indicate whether to revoke the refresh token or not. The method returns a boolean value indicating whether the logout was successful or not.

```php
...
Auth::guard('api')->logout();

...
Auth::guard('api')->logout(true); //Revoke the Refresh Token.
```


## **Refresh Token**

The package provides you with a trait that makes the refresh token process very simple. The package provides a trait `RefreshToken` that you can add to your controller to make the refresh token process functional. The namespace for the trait is `Ellaisys\Cognito\Auth\RefreshToken`. The trait has the capability to handle the following refresh token types:
    - `refresh` (Refresh the access token using the refresh token)
    - `revalidate` (Authenticate using the refresh token)

The `refresh` method requires an active access token to be present in the request. The `revalidate` method does not require an active access token to be present in the request. The `revalidate` method is useful in scenarios where the access token has expired and you want to re-authenticate the user using the refresh token.

The package provides routes and controllers for the refresh and revalidate token process. You can use the preconfigured controller and routes provided by us or you can implement your own controller and routes.

```php
//Route to refresh a token
use Ellaisys\Cognito\Http\Controllers\Auth\RefreshTokenController;
...
Route::post('/token/revalidate', [RefreshTokenController::class, 'revalidate']);
Route::post('/token/refresh', [RefreshTokenController::class, 'refresh']);
```

#### Advanced Refresh Token Options

In case you want to customize the refresh token process, and write your own controller, you can use the `refresh` method provided by the trait. The method takes a collection of user data and refreshes the access token using the refresh token. The method returns a claim object on successful refresh.

```php
...
use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\RefreshToken;

class RefreshTokenController extends Controller
{
    use RefreshToken;

    /**
     * Generate a new claim using the revalidate approach
     */
    public function revalidateToken(\Illuminate\Http\Request $request)
    {
        ...
        $validator = $request->validate([
            'email' => 'required|email',
            'refresh_token' => 'required'
        ]);
        
        return $this->revalidate($request, 'email', 'refresh_token');
    } //Function ends

    /**
     * Generate a new claim using refresh token.
     * @return mixed
     */
    public function refreshToken(\Illuminate\Http\Request $request)
    {
        ...
        return $this->refresh($request);
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

- Ellaisys\Cognito\Auth\ResetsPasswords
- Ellaisys\Cognito\Auth\SendsPasswordResetEmails

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

> [!NOTE]
> The Access Token is now validated with the AWS Cognito certificate. If the certificate is incorrect or expired, it will throw am exception.