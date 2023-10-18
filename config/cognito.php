<?php

use Ellaisys\Cognito\AwsCognitoClient;

return [
    /*
    |--------------------------------------------------------------------------
    | AWS configurations
    |--------------------------------------------------------------------------
    |
    | If you have created the aws iam users, you should set the details from
    | the aws console within your environment file. These values will
    | get used while connecting with the aws using the official sdk.
    |
    */
    'credentials'       => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'token' => null
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS Cognito configurations
    |--------------------------------------------------------------------------
    |
    | If you have created the aws cognito , you should set the details from
    | the aws console within your environment file. These values will
    | get used while issuing fresh personal access tokens to your users.
    |
    */
    'app_client_id'     => env('AWS_COGNITO_CLIENT_ID'),
    'app_client_secret' => env('AWS_COGNITO_CLIENT_SECRET'),
    'user_pool_id'      => env('AWS_COGNITO_USER_POOL_ID'),
    'region'            => env('AWS_COGNITO_REGION', 'us-east-1'),
    'version'           => env('AWS_COGNITO_VERSION', 'latest'),

    /*
    |--------------------------------------------------------------------------
    | AWS Cognito for Allowing the App Client Secret
    |--------------------------------------------------------------------------
    |
    | If you have created the aws cognito, and don't plan to set the client
    | secret, then use this configuration to help. By default we expect the
    | client secret is configured and available.
    |
    */
    'app_client_secret_allow' => env('AWS_COGNITO_CLIENT_SECRET_ALLOW', true),

    /*
    |--------------------------------------------------------------------------
    | AWS Cognito Path
    |--------------------------------------------------------------------------
    |
    | This is the base URI path where the package views, such as the MFA
    | verification screen, activation screen, etc will be available from. 
    | You're free to tweak this path according to your preferences and 
    | application design.
    |
    */
    'path' => env('AWS_COGNITO_PATH', ''),

    /*
    |--------------------------------------------------------------------------
    | API Prefix Path
    |--------------------------------------------------------------------------
    |
    | This is the API Prefix for the endpoint. You can change is such that it
    | does not cause any conflict with your existing API endpoints or as per
    | your application design.
    |
    | The default value is set to 'cognito'.
    |
    */
    'api_prefix' => env('AWS_COGNITO_API_PREFIX', 'cognito'),

    /*
    |--------------------------------------------------------------------------
    | AWS Cognito Home Route Name
    |--------------------------------------------------------------------------
    |
    | This is the home route name where the page redirects post successful 
    | authentication. This will work with and without the MFA enabled using
    | the AWS Cognito based authentication.
    |
    | The default value is set to 'home'.
    |
    */
    'redirect_to_route_name' => env('AWS_COGNITO_HOME_ROUTE_NAME', 'home'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Fields & DB Mapping
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito fields that shall be needed to be
    | updated. The array value is a mapping with DB model or Request data.
    |
    */
    'cognito_user_fields'   => [
        'name' => 'name',
        'given_name' => null,
        'middle_name' => null,
        'family_name' => null,
        'nickname' => null,
        'preferred_username' => null,
        'email' => 'email', //Do Not set this parameter to null
        'phone_number' => 'phone',
        'gender' => null,
        'birthdate' => null,
        'locale' => null
    ],

    /*
    |--------------------------------------------------------------------------
    | Cognito New User
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito when a new user is add to the
    | User Pool.
    |
    | The options available are "NONE", "BOTH", "EMAIL", "SMS"
    |
    */
    'add_user_delivery_mediums' => env('AWS_COGNITO_ADD_USER_DELIVERY_MEDIUMS', 'BOTH'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Default User Group
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito user group assigned to a user
    | when added to a User Pool.  Leave null if not assigning a group on
    | registration.
    |
    |
    */
    'default_user_group' => env('AWS_COGNITO_DEFAULT_USER_GROUP', null),

    /*
    |--------------------------------------------------------------------------
    | Cognito MFA Setup and configurations
    |--------------------------------------------------------------------------
    |
    | This option controls the cognito MFA configuration for the assigned user.
    | 
    |
    | MFA_NONE, MFA_ENABLED
    |
    */
    'mfa_setup' => env('AWS_COGNITO_MFA_SETUP', 'MFA_NONE'),
    'force_mfa_code_route_name' => env('AWS_COGNITO_MFA_CODE_ROUTE_NAME', 'login'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Default User Group
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito user group assigned to a user
    | when added to a User Pool.  Leave null if not assigning a group on
    | registration.
    |
    | SMS_MFA, SOFTWARE_TOKEN_MFA
    |
    */
    'mfa_type' => env('AWS_COGNITO_MFA_TYPE', 'SOFTWARE_TOKEN_MFA'),

    /*
    |--------------------------------------------------------------------------
    | SSO Settings
    |--------------------------------------------------------------------------
    |
    | This option controls the SSO settings into the application.
    |
    */
    'add_missing_local_user_sso'    => env('AWS_COGNITO_ADD_LOCAL_USER', false),
    'delete_user'                   => env('AWS_COGNITO_DELETE_USER', false),

    // Package configurations
    'sso_user_model'        => env('AWS_COGNITO_USER_MODEL', 'App\User'),

    /*
    |--------------------------------------------------------------------------
    | Token Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default store connection provider that gets used
    | while persisting the token. You can use the providers in the cache config.
    |
    */
    'storage_provider' => env('AWS_COGNITO_TOKEN_STORAGE', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cognito Challenge Status Names for Forced Access.
    |--------------------------------------------------------------------------
    |
    | This option controls the package action based on the Challenge Status
    | received from the AWS Cognito Authentication. If the challenge status
    | is 'NEW_PASSWORD_CHALLENGE' and/or 'RESET_REQUIRED_PASSWORD', the
    | configuration that follows below will execute.
    |
    */
    'forced_challenge_names' => [
        AwsCognitoClient::NEW_PASSWORD_CHALLENGE,
        AwsCognitoClient::RESET_REQUIRED_PASSWORD
    ],

    /*
    |--------------------------------------------------------------------------
    | Force Password Change by the User based on Cognito Status in Web Request (Session Guard)
    |--------------------------------------------------------------------------
    |
    | This setting controls the action, in case the AWS Cognito authentication
    | response includes the Challenge Names defined by 'forced_challenge_names'
    | configuration in this file. The below flag, if set to 'true', will force
    | the web application user to be directed to certain route view/page.
    |
    | In case the route name needs to be changed, you can set the below parameter
    | and map it in web.php route page.
    |
    */
    'force_password_change_web' => env('AWS_COGNITO_FORCE_PASSWORD_CHANGE_WEB', true),
    'force_redirect_route_name' => env('AWS_COGNITO_FORCE_PASSWORD_ROUTE_NAME', 'cognito.form.change.password'),

    /*
    |--------------------------------------------------------------------------
    | Force Password Change by User based on Cognito Status in API Request (Token Guard)
    |--------------------------------------------------------------------------
    |
    | This setting controls the action, in case the AWS Cognito authentication
    | response includes the Challenge Names defined by 'forced_challenge_names'
    | configuration in this file. The below flag, if set to 'true', will force
    | the user requesting API authentication by sharing the data required for
    | changing the password.
    |
    */
    'force_password_change_api' => env('AWS_COGNITO_FORCE_PASSWORD_CHANGE_API', true),

    /*
    |--------------------------------------------------------------------------
    | Force Auto Password Update based on Cognito Status in API Request (Token Guard)
    |--------------------------------------------------------------------------
    |
    | This option enables the password to be auto updated into the AWS Cognito
    | User Pool. This feature will work only if the 'force_password_change_api'
    | is set to false.
    |
    */
    'force_password_auto_update_api' => env('AWS_COGNITO_FORCE_PASSWORD_AUTO_UPDATE_API', false),

    /*
    |--------------------------------------------------------------------------
    | Allow forgot password to resend the request based on Cognito User Status
    |--------------------------------------------------------------------------
    |
    | This option enables the user to request for password from the AWS Cognito
    | User Pool, where the user is not with confirmed status.
    |
    */
    'allow_forgot_password_resend' => env('AWS_COGNITO_ALLOW_FORGOT_PASSWORD_RESEND', true),

    /*
    |--------------------------------------------------------------------------
    | Allow new user email address to be verified during invitation
    |--------------------------------------------------------------------------
    |
    | This option enables the user email address to be tagged as verified during
    | the to invitation for the new user. The default value is set to true.
    |
    */
    'force_new_user_email_verified' => env('AWS_COGNITO_FORCE_NEW_USER_EMAIL_VERIFIED', false),

    /*
    |--------------------------------------------------------------------------
    | Set the parameters for the new user message action
    |--------------------------------------------------------------------------
    |
    | This option enables the new user message action. You can set the value to
    | SUPPRESS in order to stop the invitation mails from being sent. The default
    | value is set to null.
    |
    | Possible values: "RESEND", "SUPPRESS"
    |
    */
    'new_user_message_action' => env('AWS_COGNITO_NEW_USER_MESSAGE_ACTION', null),

    /*
    |--------------------------------------------------------------------------
    | Allow new user to set the password and have verified
    |--------------------------------------------------------------------------
    |
    | This option enables the user to set the password and have that verified 
    | during the to invitation for the new user. The default value is set to true.
    |
    */
    'force_new_user_password' => env('AWS_COGNITO_FORCE_NEW_USER_PASSWORD', false),
];
