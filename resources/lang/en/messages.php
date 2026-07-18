<?php
 
// lang/en/messages.php
 
return [
    'welcome' => 'Welcome to our application!',

    'email_address' => 'Email Address',
    'pass_code' => 'Pass Code',

    'error' => [
        'ERROR_COGNITO_DEFAULT' => 'An error occurred while processing your request. Please try again later.',
        'ERROR_COGNITO_AUTH_USER_UNAUTHORIZED' => 'User is not authorized to perform this action.',
        'ERROR_COGNITO_AUTH_USERNAME_EXISTS' => 'User already exists. Please provide different details.',
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
    ],
];
