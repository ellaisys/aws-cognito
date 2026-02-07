# Passwordless Authentication with AWS Cognito

## Executive Summary

This document provides a comprehensive analysis of passwordless authentication options using AWS Cognito, including implementation strategies, API methods, security considerations, and integration recommendations for the `ellaisys/aws-cognito` Laravel package.

## Table of Contents

1. [Overview](#overview)
2. [Passwordless Authentication Methods](#passwordless-authentication-methods)
3. [AWS Cognito Passwordless Capabilities](#aws-cognito-passwordless-capabilities)
4. [Implementation Approaches](#implementation-approaches)
5. [API Reference](#api-reference)
6. [Security Best Practices](#security-best-practices)
7. [Integration Roadmap](#integration-roadmap)
8. [Code Examples](#code-examples)
9. [References](#references)

---

## Overview

Passwordless authentication eliminates the need for users to remember and enter passwords, improving both security and user experience. AWS Cognito supports multiple passwordless authentication methods that can be integrated with Laravel applications.

### Benefits of Passwordless Authentication

- **Enhanced Security**: Eliminates password-related vulnerabilities (weak passwords, password reuse, phishing)
- **Improved User Experience**: Faster login process without password memorization
- **Reduced Support Costs**: No password reset requests
- **Compliance**: Meets modern security standards and regulations
- **Mobile-Friendly**: Better experience on mobile devices

---

## Passwordless Authentication Methods

### 1. SMS/Email One-Time Password (OTP)

**Description**: Users receive a temporary code via SMS or email to authenticate.

**Pros**:
- Easy to implement
- Wide user adoption (no special hardware required)
- Works on any device

**Cons**:
- SMS can be intercepted (SIM swapping attacks)
- Requires phone number/email verification
- SMS costs can add up

**Use Cases**: Consumer applications, mobile apps, quick registration flows

---

### 2. Magic Links (Email-Based)

**Description**: Users receive a unique, time-limited authentication link via email.

**Pros**:
- No need to type codes
- Simple user experience
- No SMS costs

**Cons**:
- Email delivery delays
- Requires email client access
- Link can be intercepted if email is compromised

**Use Cases**: Web applications, admin panels, infrequent access scenarios

---

### 3. WebAuthn/FIDO2 (Biometric/Hardware Keys)

**Description**: Uses device biometrics (fingerprint, face recognition) or hardware security keys.

**Pros**:
- Highest security level
- Excellent user experience
- Phishing-resistant
- Industry standard (FIDO2)

**Cons**:
- Requires modern devices/browsers
- Complex implementation
- Device dependency

**Use Cases**: High-security applications, financial services, enterprise applications

---

### 4. Push Notifications (Mobile App)

**Description**: Users approve authentication requests on their mobile device.

**Pros**:
- Secure and convenient
- Good user experience
- Real-time notifications

**Cons**:
- Requires mobile app
- Complex implementation
- Network dependency

**Use Cases**: Banking apps, enterprise mobile applications

---

## AWS Cognito Passwordless Capabilities

### Native Cognito Support

AWS Cognito provides several features that enable passwordless authentication:

#### 1. **Custom Authentication Flow (Lambda Triggers)**

Cognito's Custom Auth Challenge allows complete control over the authentication process using Lambda functions:

- **Define Auth Challenge**: Determines the next challenge
- **Create Auth Challenge**: Generates the challenge (OTP, magic link)
- **Verify Auth Challenge**: Validates the user's response

**Key Lambda Triggers**:
```
DefineAuthChallenge
CreateAuthChallenge
VerifyAuthChallengeResponse
```

#### 2. **SMS and Email OTP (Built-in)**

Cognito natively supports:
- SMS-based MFA (can be used for passwordless)
- Email verification codes
- Configurable code expiration
- Customizable message templates

#### 3. **WebAuthn Support (Passkeys)**

AWS Cognito now supports WebAuthn/FIDO2 authentication:
- Biometric authentication (Touch ID, Face ID, Windows Hello)
- Hardware security keys (YubiKey, etc.)
- Platform authenticators
- Cross-platform authenticators

**Availability**: Generally available as of 2024 across all regions

---

## Implementation Approaches

### Approach 1: Custom Authentication Flow with Lambda

**Architecture**:
```
User → Cognito User Pool → Lambda Functions → Cognito
```

**Flow**:
1. User initiates auth with username (no password)
2. `DefineAuthChallenge` Lambda determines challenge type
3. `CreateAuthChallenge` Lambda generates OTP and sends via SMS/email
4. User submits OTP
5. `VerifyAuthChallengeResponse` Lambda validates OTP
6. Cognito returns tokens upon successful verification

**Cognito API Methods**:
```php
// Initiate custom auth
$client->adminInitiateAuth([
    'AuthFlow' => 'CUSTOM_AUTH',
    'ClientId' => $clientId,
    'UserPoolId' => $userPoolId,
    'AuthParameters' => [
        'USERNAME' => $username,
    ],
]);

// Respond to auth challenge
$client->adminRespondToAuthChallenge([
    'ChallengeName' => 'CUSTOM_CHALLENGE',
    'ClientId' => $clientId,
    'UserPoolId' => $userPoolId,
    'ChallengeResponses' => [
        'USERNAME' => $username,
        'ANSWER' => $otpCode,
    ],
    'Session' => $session,
]);
```

**Lambda Function Example (Define Auth Challenge)**:
```javascript
exports.handler = async (event) => {
    if (event.request.session.length === 0) {
        // First attempt - send OTP
        event.response.issueTokens = false;
        event.response.failAuthentication = false;
        event.response.challengeName = 'CUSTOM_CHALLENGE';
    } else if (event.request.session.length === 1 && 
               event.request.session[0].challengeName === 'CUSTOM_CHALLENGE' &&
               event.request.session[0].challengeResult === true) {
        // Correct OTP - issue tokens
        event.response.issueTokens = true;
        event.response.failAuthentication = false;
    } else {
        // Wrong OTP
        event.response.issueTokens = false;
        event.response.failAuthentication = true;
    }
    return event;
};
```

**Lambda Function Example (Create Auth Challenge)**:
```javascript
const AWS = require('aws-sdk');
const sns = new AWS.SNS();

exports.handler = async (event) => {
    if (event.request.challengeName === 'CUSTOM_CHALLENGE') {
        // Generate 6-digit OTP
        const otp = Math.floor(100000 + Math.random() * 900000).toString();
        
        // Store OTP in privateChallengeParameters (not visible to client)
        event.response.privateChallengeParameters = {
            answer: otp
        };
        
        // Send OTP via SNS (SMS)
        const phoneNumber = event.request.userAttributes.phone_number;
        await sns.publish({
            Message: `Your verification code is: ${otp}`,
            PhoneNumber: phoneNumber
        }).promise();
        
        // Public parameters visible to client
        event.response.publicChallengeParameters = {
            message: 'OTP sent to your phone'
        };
    }
    return event;
};
```

**Lambda Function Example (Verify Auth Challenge)**:
```javascript
exports.handler = async (event) => {
    const expectedAnswer = event.request.privateChallengeParameters.answer;
    const userAnswer = event.request.challengeAnswer;
    
    event.response.answerCorrect = (expectedAnswer === userAnswer);
    
    return event;
};
```

---

### Approach 2: WebAuthn/Passkeys Integration

**Architecture**:
```
User Device → Browser WebAuthn API → Cognito User Pool → Authenticator
```

**Setup Requirements**:
1. Enable WebAuthn in Cognito User Pool
2. Configure relying party (domain)
3. Implement WebAuthn registration and authentication flows

**Registration Flow**:
```php
// 1. Get registration options from Cognito
$client->startWebAuthnRegistration([
    'AccessToken' => $accessToken,
]);

// 2. Client-side: Use WebAuthn API to create credential
// navigator.credentials.create(publicKeyCredentialCreationOptions)

// 3. Complete registration
$client->completeWebAuthnRegistration([
    'AccessToken' => $accessToken,
    'Credential' => [
        'CredentialId' => $credentialId,
        'PublicKey' => $publicKey,
        // ... other credential data
    ],
]);
```

**Authentication Flow**:
```php
// 1. Initiate WebAuthn authentication
$client->initiateAuth([
    'AuthFlow' => 'CUSTOM_AUTH',
    'ClientId' => $clientId,
    'AuthParameters' => [
        'USERNAME' => $username,
        'CHALLENGE_NAME' => 'WEB_AUTHN',
    ],
]);

// 2. Client-side: Get assertion from authenticator
// navigator.credentials.get(publicKeyCredentialRequestOptions)

// 3. Verify assertion
$client->respondToAuthChallenge([
    'ChallengeName' => 'WEB_AUTHN',
    'ClientId' => $clientId,
    'Session' => $session,
    'ChallengeResponses' => [
        'USERNAME' => $username,
        'CREDENTIAL_ASSERTION' => $assertion,
    ],
]);
```

---

### Approach 3: Magic Link via Email

**Architecture**:
```
User → Enter Email → Lambda Generates Link → SES Sends Email → User Clicks → Verify & Authenticate
```

**Implementation Steps**:

1. **Generate Secure Token**:
```javascript
// Lambda: Create Auth Challenge
const crypto = require('crypto');
const AWS = require('aws-sdk');
const ses = new AWS.SES();

exports.handler = async (event) => {
    // Generate secure token
    const token = crypto.randomBytes(32).toString('hex');
    const expiresAt = Date.now() + (15 * 60 * 1000); // 15 minutes
    
    // Store token in DynamoDB or parameter store
    // (associate with user email and expiration)
    
    // Send magic link via SES
    const email = event.request.userAttributes.email;
    const magicLink = `https://yourdomain.com/auth/verify?token=${token}`;
    
    await ses.sendEmail({
        Source: 'noreply@yourdomain.com',
        Destination: { ToAddresses: [email] },
        Message: {
            Subject: { Data: 'Your Login Link' },
            Body: {
                Html: {
                    Data: `<p>Click here to login: <a href="${magicLink}">Login</a></p>
                           <p>This link expires in 15 minutes.</p>`
                }
            }
        }
    }).promise();
    
    event.response.privateChallengeParameters = {
        token: token,
        expiresAt: expiresAt.toString()
    };
    
    return event;
};
```

2. **Verify Token**:
```javascript
// Lambda: Verify Auth Challenge Response
exports.handler = async (event) => {
    const expectedToken = event.request.privateChallengeParameters.token;
    const expiresAt = parseInt(event.request.privateChallengeParameters.expiresAt);
    const userToken = event.request.challengeAnswer;
    
    // Check token validity and expiration
    const isValid = (expectedToken === userToken && Date.now() < expiresAt);
    
    event.response.answerCorrect = isValid;
    
    return event;
};
```

---

## API Reference

### Key AWS Cognito API Methods for Passwordless

#### 1. **AdminInitiateAuth**
Initiates authentication flow (server-side).

```php
$response = $client->adminInitiateAuth([
    'AuthFlow' => 'CUSTOM_AUTH', // For passwordless
    'ClientId' => 'string',
    'UserPoolId' => 'string',
    'AuthParameters' => [
        'USERNAME' => 'string',
        // No PASSWORD parameter
    ],
    'ClientMetadata' => [ // Optional context
        'loginType' => 'passwordless',
    ],
]);
```

**Response Structure**:
```php
[
    'ChallengeName' => 'CUSTOM_CHALLENGE',
    'Session' => 'string', // Session token for subsequent calls
    'ChallengeParameters' => [
        // Public parameters from CreateAuthChallenge
    ],
]
```

---

#### 2. **AdminRespondToAuthChallenge**
Responds to authentication challenge.

```php
$response = $client->adminRespondToAuthChallenge([
    'ChallengeName' => 'CUSTOM_CHALLENGE',
    'ClientId' => 'string',
    'UserPoolId' => 'string',
    'Session' => 'string', // From previous response
    'ChallengeResponses' => [
        'USERNAME' => 'string',
        'ANSWER' => 'string', // OTP or token
    ],
]);
```

**Success Response**:
```php
[
    'AuthenticationResult' => [
        'AccessToken' => 'string',
        'IdToken' => 'string',
        'RefreshToken' => 'string',
        'ExpiresIn' => 3600,
        'TokenType' => 'Bearer',
    ],
]
```

---

#### 3. **InitiateAuth**
Client-side authentication initiation.

```php
$response = $client->initiateAuth([
    'AuthFlow' => 'CUSTOM_AUTH',
    'ClientId' => 'string',
    'AuthParameters' => [
        'USERNAME' => 'string',
    ],
]);
```

---

#### 4. **RespondToAuthChallenge**
Client-side challenge response.

```php
$response = $client->respondToAuthChallenge([
    'ChallengeName' => 'CUSTOM_CHALLENGE',
    'ClientId' => 'string',
    'Session' => 'string',
    'ChallengeResponses' => [
        'USERNAME' => 'string',
        'ANSWER' => 'string',
    ],
]);
```

---

#### 5. **SetUserMFAPreference** (for SMS OTP)
Configure user MFA preferences.

```php
$client->setUserMFAPreference([
    'AccessToken' => 'string',
    'SMSMfaSettings' => [
        'Enabled' => true,
        'PreferredMfa' => true,
    ],
]);
```

---

## Security Best Practices

### 1. **OTP Security**

- **Length**: Use at least 6-digit codes (8 recommended for high security)
- **Expiration**: Set short expiration times (5-15 minutes)
- **Rate Limiting**: Implement attempt limits (3-5 attempts)
- **One-time Use**: Invalidate OTP after successful use
- **Secure Delivery**: Use HTTPS for all communications

**Example Rate Limiting**:
```javascript
// Lambda: Track failed attempts
const attempts = parseInt(event.request.userAttributes['custom:login_attempts'] || '0');

if (attempts >= 5) {
    event.response.issueTokens = false;
    event.response.failAuthentication = true;
    throw new Error('Too many failed attempts');
}
```

---

### 2. **Magic Link Security**

- **Token Entropy**: Use cryptographically secure random tokens (32+ bytes)
- **Single Use**: Invalidate token after use
- **Expiration**: Short validity period (15-30 minutes)
- **HTTPS Only**: Always use secure connections
- **State Validation**: Prevent CSRF attacks with state parameters

---

### 3. **WebAuthn Security**

- **Attestation**: Verify authenticator attestation
- **User Verification**: Require user verification (PIN, biometric)
- **Credential Storage**: Securely store credential IDs
- **Fallback Method**: Provide alternative authentication method

---

### 4. **General Security Measures**

- **Account Lockout**: Implement account lockout after multiple failed attempts
- **Audit Logging**: Log all authentication attempts
- **Device Fingerprinting**: Track devices for anomaly detection
- **IP Whitelisting**: Optional for sensitive applications
- **Session Management**: Implement secure session handling

---

## Integration Roadmap

### Phase 1: Foundation (Weeks 1-2)

**Objectives**:
- Research and finalize passwordless approach
- Set up Lambda functions in AWS
- Configure Cognito User Pool for custom auth

**Tasks**:
1. Create Lambda functions for custom auth flow
2. Update Cognito User Pool trigger configuration
3. Set up SNS/SES for message delivery
4. Test Lambda functions independently

**Deliverables**:
- Working Lambda functions
- Configured Cognito User Pool
- Test results documentation

---

### Phase 2: API Integration (Weeks 3-4)

**Objectives**:
- Extend `AwsCognitoClient` class for passwordless methods
- Implement passwordless authentication traits
- Add configuration options

**Tasks**:
1. Create new trait `PasswordlessAuthentication`
2. Add methods to `AwsCognitoClient`:
   - `initiatePasswordlessAuth()`
   - `verifyPasswordlessCode()`
   - `resendPasswordlessCode()`
3. Update configuration file
4. Add error handling

**Deliverables**:
- Updated `AwsCognitoClient.php`
- New trait file
- Updated configuration

---

### Phase 3: Laravel Integration (Weeks 5-6)

**Objectives**:
- Create Laravel controllers and routes
- Implement middleware
- Add validation rules

**Tasks**:
1. Create `PasswordlessAuthController`
2. Add routes for passwordless flows
3. Create request validation classes
4. Update authentication middleware
5. Add Blade components for UI

**Deliverables**:
- Controller files
- Route definitions
- Validation classes
- UI components

---

### Phase 4: Testing & Documentation (Weeks 7-8)

**Objectives**:
- Comprehensive testing
- Update documentation
- Create demo application

**Tasks**:
1. Write unit tests
2. Write integration tests
3. Update README.md
4. Create separate PASSWORDLESS.md guide
5. Update demo application
6. Security audit

**Deliverables**:
- Test suite
- Documentation
- Updated demo app
- Security audit report

---

## Code Examples

### Example 1: SMS OTP Passwordless Authentication

**Controller Method**:
```php
<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Ellaisys\Cognito\AwsCognitoClient;

class PasswordlessAuthController extends Controller
{
    protected $cognitoClient;
    
    public function __construct(AwsCognitoClient $cognitoClient)
    {
        $this->cognitoClient = $cognitoClient;
    }
    
    /**
     * Initiate passwordless authentication
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);
        
        try {
            $response = $this->cognitoClient->adminInitiateAuth([
                'AuthFlow' => 'CUSTOM_AUTH',
                'ClientId' => config('cognito.app_client_id'),
                'UserPoolId' => config('cognito.user_pool_id'),
                'AuthParameters' => [
                    'USERNAME' => $request->phone_number,
                ],
                'ClientMetadata' => [
                    'auth_type' => 'sms_otp',
                ],
            ]);
            
            // Store session for later verification
            session(['cognito_session' => $response['Session']]);
            session(['cognito_username' => $request->phone_number]);
            
            return response()->json([
                'message' => 'OTP sent successfully',
                'challenge' => $response['ChallengeName'],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send OTP',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
    
    /**
     * Verify OTP and complete authentication
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);
        
        try {
            $response = $this->cognitoClient->adminRespondToAuthChallenge([
                'ChallengeName' => 'CUSTOM_CHALLENGE',
                'ClientId' => config('cognito.app_client_id'),
                'UserPoolId' => config('cognito.user_pool_id'),
                'Session' => session('cognito_session'),
                'ChallengeResponses' => [
                    'USERNAME' => session('cognito_username'),
                    'ANSWER' => $request->otp,
                ],
            ]);
            
            if (isset($response['AuthenticationResult'])) {
                // Store tokens
                $tokens = $response['AuthenticationResult'];
                
                // Login user with tokens
                Auth::guard('web')->loginUsingToken($tokens['IdToken']);
                
                return response()->json([
                    'message' => 'Authentication successful',
                    'access_token' => $tokens['AccessToken'],
                    'id_token' => $tokens['IdToken'],
                    'refresh_token' => $tokens['RefreshToken'],
                ]);
            }
            
            return response()->json([
                'error' => 'Invalid OTP',
            ], 401);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Verification failed',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

---

### Example 2: Trait for Passwordless Authentication

**File**: `src/Auth/PasswordlessAuthentication.php`

```php
<?php

namespace Ellaisys\Cognito\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ellaisys\Cognito\AwsCognitoClient;

trait PasswordlessAuthentication
{
    /**
     * Initiate passwordless authentication
     *
     * @param string $username
     * @param string $method (sms|email|magic_link)
     * @return array
     */
    protected function initiatePasswordlessAuth(
        string $username,
        string $method = 'sms'
    ): array {
        $client = app()->make(AwsCognitoClient::class);
        
        $response = $client->adminInitiateAuth([
            'AuthFlow' => 'CUSTOM_AUTH',
            'ClientId' => config('cognito.app_client_id'),
            'UserPoolId' => config('cognito.user_pool_id'),
            'AuthParameters' => [
                'USERNAME' => $username,
            ],
            'ClientMetadata' => [
                'auth_method' => $method,
            ],
        ]);
        
        return $response;
    }
    
    /**
     * Verify passwordless challenge
     *
     * @param string $username
     * @param string $answer
     * @param string $session
     * @return array
     */
    protected function verifyPasswordlessChallenge(
        string $username,
        string $answer,
        string $session
    ): array {
        $client = app()->make(AwsCognitoClient::class);
        
        $response = $client->adminRespondToAuthChallenge([
            'ChallengeName' => 'CUSTOM_CHALLENGE',
            'ClientId' => config('cognito.app_client_id'),
            'UserPoolId' => config('cognito.user_pool_id'),
            'Session' => $session,
            'ChallengeResponses' => [
                'USERNAME' => $username,
                'ANSWER' => $answer,
            ],
        ]);
        
        return $response;
    }
    
    /**
     * Resend passwordless code
     *
     * @param string $username
     * @param string $method
     * @return array
     */
    protected function resendPasswordlessCode(
        string $username,
        string $method = 'sms'
    ): array {
        return $this->initiatePasswordlessAuth($username, $method);
    }
}
```

---

### Example 3: WebAuthn Registration

**JavaScript (Frontend)**:
```javascript
// Register WebAuthn Credential
async function registerWebAuthn() {
    try {
        // 1. Get registration options from server
        const optionsResponse = await fetch('/api/webauthn/register/options', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: 'user@example.com' })
        });
        
        const options = await optionsResponse.json();
        
        // 2. Convert base64 strings to ArrayBuffer
        options.publicKey.challenge = base64ToArrayBuffer(options.publicKey.challenge);
        options.publicKey.user.id = base64ToArrayBuffer(options.publicKey.user.id);
        
        // 3. Create credential using WebAuthn API
        const credential = await navigator.credentials.create(options);
        
        // 4. Send credential to server
        const registerResponse = await fetch('/api/webauthn/register/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                credentialId: arrayBufferToBase64(credential.rawId),
                publicKey: arrayBufferToBase64(credential.response.attestationObject),
                clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
            })
        });
        
        const result = await registerResponse.json();
        console.log('Registration successful:', result);
        
    } catch (error) {
        console.error('WebAuthn registration failed:', error);
    }
}

// Helper functions
function base64ToArrayBuffer(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}
```

---

### Example 4: Configuration Updates

**File**: `config/cognito.php`

```php
<?php

return [
    // Existing configuration...
    
    /*
    |--------------------------------------------------------------------------
    | Passwordless Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration for passwordless authentication features
    |
    */
    
    'passwordless' => [
        
        /*
         | Enable passwordless authentication
         */
        'enabled' => env('COGNITO_PASSWORDLESS_ENABLED', false),
        
        /*
         | Default passwordless method (sms|email|magic_link|webauthn)
         */
        'default_method' => env('COGNITO_PASSWORDLESS_METHOD', 'sms'),
        
        /*
         | OTP configuration
         */
        'otp' => [
            'length' => env('COGNITO_OTP_LENGTH', 6),
            'expiry_minutes' => env('COGNITO_OTP_EXPIRY', 5),
            'max_attempts' => env('COGNITO_OTP_MAX_ATTEMPTS', 3),
        ],
        
        /*
         | Magic link configuration
         */
        'magic_link' => [
            'expiry_minutes' => env('COGNITO_MAGIC_LINK_EXPIRY', 15),
            'base_url' => env('APP_URL') . '/auth/verify',
        ],
        
        /*
         | SMS configuration
         */
        'sms' => [
            'from' => env('COGNITO_SMS_FROM', 'YourApp'),
            'template' => 'Your verification code is: {####}',
        ],
        
        /*
         | Email configuration
         */
        'email' => [
            'from' => env('COGNITO_EMAIL_FROM', 'noreply@example.com'),
            'from_name' => env('COGNITO_EMAIL_FROM_NAME', 'YourApp'),
            'subject' => 'Your Login Code',
        ],
        
        /*
         | WebAuthn configuration
         */
        'webauthn' => [
            'rp_name' => env('COGNITO_WEBAUTHN_RP_NAME', config('app.name')),
            'rp_id' => env('COGNITO_WEBAUTHN_RP_ID', parse_url(env('APP_URL'), PHP_URL_HOST)),
            'timeout' => env('COGNITO_WEBAUTHN_TIMEOUT', 60000), // milliseconds
        ],
    ],
];
```

---

### Example 5: Routes Configuration

**File**: `routes/web.php`

```php
<?php

use App\Http\Controllers\Auth\PasswordlessAuthController;

// Passwordless Authentication Routes
Route::prefix('auth/passwordless')->group(function () {
    
    // Send OTP
    Route::post('/send-otp', [PasswordlessAuthController::class, 'sendOtp'])
        ->name('auth.passwordless.send-otp');
    
    // Verify OTP
    Route::post('/verify-otp', [PasswordlessAuthController::class, 'verifyOtp'])
        ->name('auth.passwordless.verify-otp');
    
    // Resend OTP
    Route::post('/resend-otp', [PasswordlessAuthController::class, 'resendOtp'])
        ->name('auth.passwordless.resend-otp');
    
    // Magic Link
    Route::post('/send-magic-link', [PasswordlessAuthController::class, 'sendMagicLink'])
        ->name('auth.passwordless.send-magic-link');
    
    Route::get('/verify-magic-link', [PasswordlessAuthController::class, 'verifyMagicLink'])
        ->name('auth.passwordless.verify-magic-link');
    
    // WebAuthn
    Route::post('/webauthn/register/options', [PasswordlessAuthController::class, 'webAuthnRegisterOptions'])
        ->name('auth.passwordless.webauthn.register.options');
    
    Route::post('/webauthn/register/verify', [PasswordlessAuthController::class, 'webAuthnRegisterVerify'])
        ->name('auth.passwordless.webauthn.register.verify');
    
    Route::post('/webauthn/login/options', [PasswordlessAuthController::class, 'webAuthnLoginOptions'])
        ->name('auth.passwordless.webauthn.login.options');
    
    Route::post('/webauthn/login/verify', [PasswordlessAuthController::class, 'webAuthnLoginVerify'])
        ->name('auth.passwordless.webauthn.login.verify');
});
```

---

### Example 6: Migration for Passwordless Metadata

**File**: `database/migrations/xxxx_xx_xx_add_passwordless_columns.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPasswordlessColumns extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Passwordless preferences
            $table->string('passwordless_method')->nullable()->after('password');
            $table->boolean('passwordless_enabled')->default(false)->after('passwordless_method');
            
            // WebAuthn credentials (JSON storage)
            $table->json('webauthn_credentials')->nullable()->after('passwordless_enabled');
            
            // OTP tracking
            $table->integer('failed_otp_attempts')->default(0)->after('webauthn_credentials');
            $table->timestamp('last_otp_attempt_at')->nullable()->after('failed_otp_attempts');
            $table->timestamp('account_locked_until')->nullable()->after('last_otp_attempt_at');
        });
    }
    
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'passwordless_method',
                'passwordless_enabled',
                'webauthn_credentials',
                'failed_otp_attempts',
                'last_otp_attempt_at',
                'account_locked_until',
            ]);
        });
    }
}
```

---

## Cost Considerations

### AWS Cognito Pricing (as of 2024)

- **Monthly Active Users (MAUs)**: First 50,000 free, then $0.0055/MAU
- **SMS MFA**: Based on Amazon SNS pricing (~$0.00645 per SMS in US)
- **Email**: Based on Amazon SES pricing (first 62,000 emails free per month)
- **Advanced Security Features**: $0.05 per MAU

### Cost Optimization Strategies

1. **Prefer Email over SMS**: Email is significantly cheaper
2. **Implement Rate Limiting**: Prevent abuse and excessive OTP sends
3. **Use WebAuthn**: No per-authentication costs
4. **Cache Tokens**: Reduce authentication requests
5. **Monitor Usage**: Set up CloudWatch alerts for unusual activity

---

## Migration Strategy

### For Existing Users

**Option 1: Gradual Migration**
- Offer passwordless as optional feature
- Allow users to opt-in
- Maintain password authentication alongside
- Phase out passwords over time

**Option 2: Forced Migration**
- Announce deprecation timeline
- Require passwordless setup at next login
- Provide grace period with fallback
- Complete switchover after deadline

**Recommended Approach**:
```php
// During login, check if user has enabled passwordless
if ($user->passwordless_enabled) {
    // Use passwordless flow
    return $this->initiatePasswordlessAuth($user->email);
} else {
    // Show option to enable passwordless
    session(['show_passwordless_promotion' => true]);
    
    // Continue with traditional password login
    return $this->attemptTraditionalLogin($credentials);
}
```

---

## Testing Considerations

### Unit Tests

```php
<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use Ellaisys\Cognito\AwsCognitoClient;
use Mockery;

class PasswordlessAuthTest extends TestCase
{
    public function test_initiate_passwordless_auth_sends_otp()
    {
        // Mock Cognito client
        $mockClient = Mockery::mock(AwsCognitoClient::class);
        $mockClient->shouldReceive('adminInitiateAuth')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg['AuthFlow'] === 'CUSTOM_AUTH' &&
                       isset($arg['AuthParameters']['USERNAME']);
            }))
            ->andReturn([
                'ChallengeName' => 'CUSTOM_CHALLENGE',
                'Session' => 'test-session-token',
            ]);
        
        $this->app->instance(AwsCognitoClient::class, $mockClient);
        
        // Test controller
        $response = $this->postJson('/auth/passwordless/send-otp', [
            'phone_number' => '+1234567890',
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'OTP sent successfully',
            ]);
    }
    
    public function test_verify_otp_with_valid_code()
    {
        // Mock successful verification
        $mockClient = Mockery::mock(AwsCognitoClient::class);
        $mockClient->shouldReceive('adminRespondToAuthChallenge')
            ->once()
            ->andReturn([
                'AuthenticationResult' => [
                    'AccessToken' => 'test-access-token',
                    'IdToken' => 'test-id-token',
                    'RefreshToken' => 'test-refresh-token',
                ],
            ]);
        
        $this->app->instance(AwsCognitoClient::class, $mockClient);
        
        session(['cognito_session' => 'test-session']);
        session(['cognito_username' => '+1234567890']);
        
        $response = $this->postJson('/auth/passwordless/verify-otp', [
            'otp' => '123456',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'access_token',
                'id_token',
                'refresh_token',
            ]);
    }
}
```

---

## References

### AWS Documentation

1. **AWS Cognito Custom Authentication Flow**
   - https://docs.aws.amazon.com/cognito/latest/developerguide/amazon-cognito-user-pools-authentication-flow.html#amazon-cognito-user-pools-custom-authentication-flow

2. **Lambda Triggers for Custom Auth**
   - https://docs.aws.amazon.com/cognito/latest/developerguide/user-pool-lambda-challenge.html

3. **WebAuthn Support in Cognito**
   - https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-user-pools-passkeys.html

4. **AWS SDK for PHP - Cognito**
   - https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-cognito-idp-2016-04-18.html

### Industry Standards

5. **FIDO2/WebAuthn Specification**
   - https://www.w3.org/TR/webauthn-2/
   - https://fidoalliance.org/fido2/

6. **OWASP Authentication Cheat Sheet**
   - https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html

7. **NIST Digital Identity Guidelines**
   - https://pages.nist.gov/800-63-3/

### Third-Party Resources

8. **WebAuthn Guide**
   - https://webauthn.guide/

9. **AWS Cognito Passwordless Examples**
   - https://github.com/aws-samples/amazon-cognito-passwordless-auth

10. **Laravel WebAuthn Package**
    - https://github.com/asbiin/laravel-webauthn

### Related Articles

11. **"The State of Passwordless Authentication"** - Auth0
    - https://auth0.com/blog/the-state-of-passwordless-authentication/

12. **"Implementing Passwordless Authentication"** - AWS Blog
    - https://aws.amazon.com/blogs/security/how-to-implement-password-less-authentication-with-amazon-cognito/

13. **"Magic Links vs OTP: Which is Better?"**
    - https://www.loginradius.com/blog/identity/magic-link-vs-otp/

---

## Conclusion

Passwordless authentication with AWS Cognito offers multiple implementation paths, each with distinct trade-offs:

### Recommended Approach for this Package

**Primary: Custom Auth Flow with SMS/Email OTP**
- Easiest to implement
- Works with existing Cognito infrastructure
- Minimal infrastructure changes
- Good user adoption

**Secondary: Magic Links**
- Better user experience for web applications
- Lower operational costs than SMS
- Suitable for email-verified users

**Future Enhancement: WebAuthn/Passkeys**
- Best long-term security solution
- Requires more complex implementation
- Growing browser support
- Ideal for high-security applications

### Next Steps

1. **Validate approach** with stakeholders and users
2. **Set up AWS infrastructure** (Lambda, SNS/SES)
3. **Implement core functionality** following phase 1-2 roadmap
4. **Test thoroughly** with various scenarios
5. **Document** for end users
6. **Monitor and iterate** based on usage patterns

### Success Metrics

- **Adoption Rate**: Percentage of users using passwordless
- **Authentication Success Rate**: Successful authentications vs. attempts
- **Time to Authenticate**: Average time from initiation to completion
- **Support Tickets**: Reduction in password-related support requests
- **Security Incidents**: Reduction in account compromises

---

**Document Version**: 1.0  
**Last Updated**: February 7, 2026  
**Author**: AWS Cognito Package Team  
**Status**: Research Complete - Ready for Implementation
