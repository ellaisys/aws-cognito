<?php
 
// lang/en/messages.php
 
return [
    'welcome' => 'Welcome to our application!',

    'email_address' => 'Email Address',
    'pass_code' => 'Pass Code',

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
