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

    // Package configurations
    'use_sso'           => env('AWS_COGNITO_USE_SSO', false),
    'sso_user_fields'   => [
        'name',
        'email',
    ],
    'delete_user'           => env('AWS_COGNITO_DELETE_USER', false),
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
    'storage_provider' => env('AWS_COGNITO_TOKEN_STORAGE', 'file')
];