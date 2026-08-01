<?php
 
// lang/en/messages.php
 
return [
    'welcome' => 'Welcome to our application!',

    'email_address' => 'Email Address',

    'view_component' => [
        'challenge' => [
            'main' => 'Challenge Main Component',
            'password' => [
                'pass_code' => 'Pass Code',
                'select_mfa_type' => 'Select MFA Type',
            ],
        ],
    ],

    'error' => [
        'ERROR_COGNITO_DEFAULT' => 'An error occurred while processing your request. Please try again later.',
        'ERROR_COGNITO_CONFIG_INVALID' => 'Cognito configuration is invalid. Please contact support.',
        'ERROR_COGNITO_AUTH_USER_UNAUTHORIZED' => 'User authentication error.',
        'ERROR_COGNITO_AUTH_USER_RESET_PASSWORD' => 'Password reset required for the user. Please reset your password to continue.',
        'ERROR_COGNITO_AUTH_USERNAME_EXISTS' => 'User already exists. Please provide different details.',
        'ERROR_COGNITO_AUTH_CODE_INVALID' => 'Invalid confirmation code. Please check the code and try again.',
        'ERROR_COGNITO_USERNAME_INVALID' => 'Invalid username. Please check the username and try again.',
        'ERROR_COGNITO_USER_INVALID' => 'User not found. Please check your credentials.',
        'ERROR_COGNITO_RESET_PWD_REQ_INVALID' => 'Password reset request is invalid. Please try again.',
        'ERROR_COGNITO_RESET_PWD_FAILED' => 'Password reset failed. Please try again later.',
        'ERROR_COGNITO_AUTH_POOL_CONFIG_INVALID' => 'Cognito pool configuration error. Please contact support.',
        'ERROR_COGNITO_THROTTLING_LIMIT' => 'Cognito throttling limit exceeded. Please try after some time.',
        'ERROR_COGNITO_WEB_AUTH_INVALID' => 'Invalid WebAuthn / Passkey. Please try again.',
        'ERROR_COGNITO_INVALID_PASSWORD' => 'Invalid password. Please check your password and try again.',
        'ERROR_COGNITO_MFA' => 'Multi-factor authentication required. Please complete the MFA process to continue.',
    ],

    'auth' => [
        'registration_success' => 'Registration successful! Please check your email for verification instructions.',
        'invitation_success' => 'User invited successfully! An invitation email has been sent to the user.',
        'login_success' => 'Login successful!',
        'logout_success' => 'Logout successful!',
        'password_reset_success' => 'Password reset successful! Please check your email for further instructions.',
        'registration_verification_success' => 'Verification successful. Please login to continue.',
        'registration_code_resend_success' => 'Verification code resent successfully. Please check your email.',
        'password_reset_code_resend_success' => 'Password reset code resent successfully. Please check your email.',
        'password_change_success' => 'Password changed successfully. Please login with your new password.',
        'challenge_generated' => 'A challenge has been generated. Please complete the required steps.',
        'error' => [
            'incorrect_credentials' => 'Incorrect username and/or password.',
            'user_not_found' => 'User not found. Please check your credentials.',
            'user_not_confirmed' => 'User not confirmed. Please check your email for verification instructions.',
            'user_disabled' => 'User account is disabled. Please contact support.',
            'password_reset_failed' => 'Password reset failed. Please try again later.',
            'registration_failed' => 'Registration failed. Please try again later.',
            'invitation_failed' => 'User invitation failed. Please try again later.',
            'challenge_failed' => 'Challenge failed. Please try again.',
        ]
    ],

    'mfa' => [
        'activation_success' => 'MFA activated successfully!',
        'deactivation_success' => 'MFA deactivated successfully!',
        'verification_success' => 'MFA verification successful!',
        'verification_failed' => 'MFA verification failed. Please try again.',
        'enabled_success' => 'MFA enabled successfully!',
        'disabled_success' => 'MFA disabled successfully!',
    ],

    'challenge' => [
        'web_authn' => 'Reviewing the Passkey',
        'email_otp' => 'Enter the OTP sent to your email',
        'sms_otp' => 'Enter the OTP sent to your phone',
        'sms_mfa' => 'Enter the OTP sent to your phone',
        'software_token_mfa' => 'Enter code from the Authenticator',
        'password_verifier' => 'Enter your password',
        'device_srp_auth' => 'Generating the Device Token',
        'device_password_verifier' => 'Validating the Device Token',
        'select_mfa_type' => 'Select the MFA type from the available options',
        'new_password_required' => 'Set a new password to continue',
    ],
];
