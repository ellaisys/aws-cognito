## **Preconfigured routes for Web and API Functionality**
To help faster implementations, we have now provided with routes for web and APIs in this package. This includes Controllers, Views and updated Configuration file. 

The new version of the package should work fine, you might have to just add some lines into the AppServiceProvider. Refer the section: [Ignore the routes and controllers](#ignore-the-routes-and-controllers)

## **Configurations**

Default configurations should work without any issues. However, you will be able to customize the package to a large extent. In case you have published the prior configuration file in your config folder, you may need to delete that and publish again. Please take backup of the prior configuration in case you have modified anything.

## **Features**
- [Preconfigured Routes & Controllers](#routes)
- [Web Views and Components]()

## **Routes**
>[!IMPORTANT]
>Preconfigured Web and API routes as a new feature from V2.0.0.

To help faster implementations, we have now provided with routes for web and APIs in this package. This includes Controllers, Views and updated Configuration file. 

The new version of the package should work fine with your existing implementation of routes and views. Just add some lines into the AppServiceProvider to ignore the routes and controllers
Refer the section: [Ignore the routes and controllers](#ignore-the-routes-and-controllers)

You can overwrite the controllers. Just publish and modify them. The controllers for Web and APIs will be saved differently in the app/Http/Controllers directory, for finer control.

```bash
    ```bash
    php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider" --tag="controllers"
```

### API Routes

The API routes that are wired via the API Controller, making it easy for users to implement. The validations are built in and API response format is standardized. 

You can change the API prefix by configuring **AWS_COGNITO_API_PREFIX** in the .env file. The default value of the AWS_COGNITO_API_PREFIX is **cognito**.

```php
    POST      api/cognito/login ............................... Ellaisys\Cognito\Http\Controllers\Auth\LoginController@login
    POST      api/cognito/login/mfa ................. Ellaisys\Cognito\Http\Controllers\Auth\MFAController@actionValidateMFA
    PUT       api/cognito/logout ............................. Ellaisys\Cognito\Http\Controllers\Auth\LoginController@logout
    PUT       api/cognito/logout/forced ................ Ellaisys\Cognito\Http\Controllers\Auth\LoginController@logoutForced
    POST      api/cognito/password/forgot .... Ellaisys\Cognito\Http\Controllers\Auth\ForgotPasswordController@sendResetLink
    POST      api/cognito/password/reset .............. Ellaisys\Cognito\Http\Controllers\Auth\ResetPasswordController@reset
    POST      api/cognito/register ...................... Ellaisys\Cognito\Http\Controllers\Auth\RegisterController@register
    POST      api/cognito/token/refresh ........... Ellaisys\Cognito\Http\Controllers\Auth\RefreshTokenController@revalidate
    POST      api/cognito/user/changepassword ...... Ellaisys\Cognito\Http\Controllers\Auth\ConfirmPasswordController@change
    POST      api/cognito/user/invite ............... Ellaisys\Cognito\Http\Controllers\Auth\RegisterController@actionInvite
    GET|HEAD  api/cognito/user/profile ............ Ellaisys\Cognito\Http\Controllers\Api\UserController@actionGetRemoteUser 

    GET|HEAD  api/cognito/user/mfa/activate .................. Ellaisys\Cognito\Http\Controllers\Auth\MFAController@activate
    POST      api/cognito/user/mfa/activate/{code} ............. Ellaisys\Cognito\Http\Controllers\Auth\MFAController@verify
    POST      api/cognito/user/mfa/deactivate .............. Ellaisys\Cognito\Http\Controllers\Auth\MFAController@deactivate
    POST      api/cognito/mfa/disable ......................... Ellaisys\Cognito\Http\Controllers\Auth\MFAController@disable
    POST      api/cognito/mfa/enable ........................... Ellaisys\Cognito\Http\Controllers\Auth\MFAController@enable

```

### Web Routes

The web routes that are wired via the Controllers, making it easy for users to implement. The validations are built in and response is wired to blade views. The views can be 

You can change the API prefix by configuring **AWS_COGNITO_WEB_PREFIX** in the .env file. The default value of the AWS_COGNITO_WEB_PREFIX is **cognito**.

```php
    GET|HEAD  cognito/home ................................................................................................................. cognito.home
    GET|HEAD  cognito/login .......................................................................................................... cognito.form.login
    POST      cognito/login .................................. cognito.action.login.submit › Ellaisys\Cognito\Http\Controllers\Auth\LoginController@login
    POST      cognito/login/mfa ..................... cognito.action.mfa.code.submit › Ellaisys\Cognito\Http\Controllers\Auth\LoginController@validateMFA  
    POST      cognito/logout ............................................. cognito.logout › Ellaisys\Cognito\Http\Controllers\Auth\LoginController@logout  
    POST      cognito/logout/forced ......................... cognito.logout_forced › Ellaisys\Cognito\Http\Controllers\Auth\LoginController@logoutForced  
    GET|HEAD  cognito/password/forgot ...................................................................................... cognito.form.password.forgot  
    POST      cognito/password/forgot .... cognito.action.password.forgot › Ellaisys\Cognito\Http\Controllers\Auth\ForgotPasswordController@sendResetLink
    GET|HEAD  cognito/password/reset ........................................................................................ cognito.form.password.reset  
    POST      cognito/password/reset ............... cognito.action.password.reset › Ellaisys\Cognito\Http\Controllers\Auth\ResetPasswordController@reset  
    GET|HEAD  cognito/register .................................................................................................... cognito.form.register  
    POST      cognito/register ...................... cognito.action.register.submit › Ellaisys\Cognito\Http\Controllers\Auth\RegisterController@register  
    POST      cognito/session/refresh ............................... cognito. › Ellaisys\Cognito\Http\Controllers\Auth\RefreshTokenController@revalidate  
    GET|HEAD  cognito/user/changepassword .................................................................................. cognito.form.change.password
    POST      cognito/user/changepassword ...... cognito.action.change.password › Ellaisys\Cognito\Http\Controllers\Auth\ConfirmPasswordController@change  
    GET|HEAD  cognito/user/mfa/activate .................. cognito.form.user.mfa.activate › Ellaisys\Cognito\Http\Controllers\Auth\MFAController@activate  
    GET|HEAD  cognito/user/mfa/deactivate .......... cognito.action.user.mfa.deactivate › Ellaisys\Cognito\Http\Controllers\Auth\MFAController@deactivate
    POST      cognito/user/mfa/verify .................... cognito.action.user.mfa.activate › Ellaisys\Cognito\Http\Controllers\Auth\MFAController@verify 
```

### Ignore the routes and controllers

If you would like to prevent AWS Cognito's routes and/or views from running entirely, you may use the ignoreRoutes and ignoreViews methods provided by AWS Cognito. Typically, this method should be called in the register method of your AppServiceProvider:

```php
    use Ellaisys\Cognito\AwsCognito;
    
    /**
     * Register any application services.
     */
    public function register(): void
    {
        AwsCognito::ignoreRoutes(); //Ignore the preconfired routes
        AwsCognito::ignoreViews();  //Ignore the views provided by the package
    }
```

## **Web Views and Components**

The package provides preconfigured blade views and components, for quick developent. The views are based on Bootstrap stylesheet.

If you need to overwrite the views that ship with this package, you can publish them using the vendor:publish Artisan command make required changes.

```bash
    php artisan vendor:publish --provider="Ellaisys\Cognito\Providers\AwsCognitoServiceProvider" --tag="views"
```

The views reference a layout file. Modify the layout after publishing the file. If your own blade layout file has to be used, amend **AWS_COGNITO_VIEWS_LAYOUT** paramter with the name of the layout file. The default value is 'cognito::layouts.app' but you can change it to 'layouts.app' to point to your own app.blade.php in the resources/views/layouts folder.
