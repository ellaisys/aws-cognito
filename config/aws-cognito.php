<?php

return [
    // AWS IAM Settings
    'credentials'       => [
        'key'    => env('AWS_IAM_KEY', 'AKIAINIMMVLQLNSYMIEQ'),
        'secret' => env('AWS_IAM_SECRET', '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'),
        'token' => null
    ],

    // Cognito Settings
    'region'            => env('AWS_COGNITO_REGION', 'ap-south-1'),
    'version'           => env('AWS_COGNITO_VERSION', 'latest'),
    'app_client_id'     => env('AWS_COGNITO_CLIENT_ID', '6qt4ahujuglcpo9bgrqnlqkm2r'),
    'app_client_secret' => env('AWS_COGNITO_CLIENT_SECRET', '1d8e41gjtt1fkbjjtb87aavks8gdu79o9bi083aa5jhv0vi074s1'),
    'user_pool_id'      => env('AWS_COGNITO_USER_POOL_ID', 'ap-south-1_VBMmtfBs8'),

    // Package configuration
    'use_sso'           => env('USE_SSO', false),
    'sso_user_fields'   => [
        'name',
        'email',
    ],

    'sso_user_model'        => 'App\User',

    'delete_user'           => env('AWS_COGNITO_DELETE_USER', false),
];