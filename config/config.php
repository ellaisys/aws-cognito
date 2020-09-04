<?php

return [
    // AWS configurations
    'credentials'       => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_ACCESS_KEY_SECRET'),
        'token' => null
    ],

    // Cognito configurations
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
];