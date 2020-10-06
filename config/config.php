<?php

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
        'secret' => env('AWS_ACCESS_KEY_SECRET'),
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
    | Cognito Fields & DB Mapping
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito fields that shall be needed to be
    | updated. The array value is a mapping with DB model or Request data.
    |
    */
    'cognito_user_fields'   => [
        'name' => 'first_name',
        'email' => 'email',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cognito New User
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito when a new user is add to the
    | User Pool.
    |
    | The options available are "DEFAULT", "EMAIL", "SMS"
    |
    */
    'add_user_delivery_mediums'     => env('AWS_COGNITO_ADD_USER_DELIVERY_MEDIUMS', 'DEFAULT'),

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
];