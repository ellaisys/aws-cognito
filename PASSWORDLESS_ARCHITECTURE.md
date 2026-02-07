# Passwordless Authentication Architecture Diagrams

## 1. SMS/Email OTP Flow

```
┌─────────────┐
│   Client    │
│ (Browser/   │
│   Mobile)   │
└──────┬──────┘
       │
       │ 1. Initiate Auth (username)
       ▼
┌─────────────────────────────────────────┐
│         Laravel Application              │
│  ┌────────────────────────────────────┐ │
│  │  PasswordlessAuthController        │ │
│  │  - sendOtp()                       │ │
│  │  - verifyOtp()                     │ │
│  └───────────────┬────────────────────┘ │
│                  │                       │
│  ┌───────────────▼────────────────────┐ │
│  │  AwsCognitoClient                  │ │
│  │  - adminInitiateAuth()             │ │
│  │  - adminRespondToAuthChallenge()   │ │
│  └───────────────┬────────────────────┘ │
└──────────────────┼──────────────────────┘
                   │
                   │ 2. Custom Auth Flow
                   ▼
       ┌──────────────────────────┐
       │  AWS Cognito User Pool   │
       │                          │
       │  ┌────────────────────┐  │
       │  │ Triggers:          │  │
       │  │ - DefineAuth       │  │
       │  │ - CreateAuth       │  │
       │  │ - VerifyAuth       │  │
       │  └─────────┬──────────┘  │
       └────────────┼──────────────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        │ 3. Lambda │           │
        ▼           ▼           ▼
  ┌──────────┐ ┌─────────┐ ┌──────────┐
  │  Define  │ │ Create  │ │  Verify  │
  │   Auth   │ │  Auth   │ │   Auth   │
  │Challenge │ │Challenge│ │Challenge │
  └──────────┘ └────┬────┘ └──────────┘
                    │
                    │ 4. Generate OTP & Send
                    ▼
              ┌──────────┐
              │ AWS SNS  │
              │ (SMS)    │
              │    or    │
              │ AWS SES  │
              │ (Email)  │
              └────┬─────┘
                   │
                   │ 5. OTP Delivery
                   ▼
              ┌─────────┐
              │  User   │
              │ Receives│
              │   OTP   │
              └────┬────┘
                   │
                   │ 6. Submit OTP
                   ▼
       ┌──────────────────────────┐
       │  Laravel Application     │
       │  verifyOtp()             │
       └──────────┬───────────────┘
                  │
                  │ 7. Verify Challenge
                  ▼
       ┌──────────────────────────┐
       │  AWS Cognito User Pool   │
       │  - Verify Lambda         │
       └──────────┬───────────────┘
                  │
                  │ 8. Issue Tokens
                  ▼
       ┌──────────────────────────┐
       │  Client receives:        │
       │  - AccessToken           │
       │  - IdToken               │
       │  - RefreshToken          │
       └──────────────────────────┘
```

---

## 2. Magic Link Flow

```
┌─────────────┐
│   Client    │
│ (Browser)   │
└──────┬──────┘
       │
       │ 1. Request Magic Link (email)
       ▼
┌─────────────────────────────────┐
│    Laravel Application          │
│  - sendMagicLink()              │
└──────────┬──────────────────────┘
           │
           │ 2. Custom Auth Flow
           ▼
    ┌──────────────────┐
    │  AWS Cognito     │
    │  + Lambda        │
    └──────┬───────────┘
           │
           │ 3. Generate Secure Token
           │    Store in DynamoDB/Cache
           ▼
    ┌──────────────┐
    │   AWS SES    │
    │ Send Email   │
    └──────┬───────┘
           │
           │ 4. Email with Link
           ▼
    ┌──────────────────────────────┐
    │  User Email Inbox            │
    │  "Click here to login"       │
    │  https://app.com/auth/       │
    │  verify?token=abc123...      │
    └──────┬───────────────────────┘
           │
           │ 5. Click Link
           ▼
    ┌──────────────────────────────┐
    │  Laravel Application         │
    │  - verifyMagicLink()         │
    │  - Verify token & expiry     │
    └──────┬───────────────────────┘
           │
           │ 6. Respond to Challenge
           ▼
    ┌──────────────────┐
    │  AWS Cognito     │
    │  Verify & Issue  │
    │  Tokens          │
    └──────┬───────────┘
           │
           │ 7. Authenticated
           ▼
    ┌──────────────────┐
    │  Client Session  │
    │  Established     │
    └──────────────────┘
```

---

## 3. WebAuthn/Passkey Flow

### Registration Flow

```
┌─────────────┐
│   Client    │
│ (Browser)   │
└──────┬──────┘
       │
       │ 1. Request Passkey Registration
       ▼
┌──────────────────────────────────────┐
│    Laravel Application               │
│  - webAuthnRegisterOptions()         │
└──────┬───────────────────────────────┘
       │
       │ 2. Get Challenge
       ▼
┌──────────────────────────┐
│  AWS Cognito             │
│  - Generate Challenge    │
│  - Public Key Options    │
└──────┬───────────────────┘
       │
       │ 3. Return Options
       ▼
┌──────────────────────────────────────┐
│  Client Browser                      │
│  navigator.credentials.create()      │
│  ┌────────────────────────────────┐  │
│  │  WebAuthn API                  │  │
│  │  - Challenge user              │  │
│  │  - Biometric/PIN               │  │
│  └────────┬───────────────────────┘  │
└───────────┼──────────────────────────┘
            │
            │ 4. User Authenticates
            │    (Fingerprint/Face/PIN)
            ▼
    ┌───────────────────┐
    │  Authenticator    │
    │  (Device/Key)     │
    │  - Generate Key   │
    │  - Sign Challenge │
    └────────┬──────────┘
             │
             │ 5. Return Credential
             ▼
    ┌────────────────────────────┐
    │  Client sends to Server:   │
    │  - Credential ID           │
    │  - Public Key              │
    │  - Attestation             │
    └────────┬───────────────────┘
             │
             │ 6. Verify & Store
             ▼
    ┌─────────────────────────────┐
    │  Laravel Application        │
    │  - webAuthnRegisterVerify() │
    └────────┬────────────────────┘
             │
             ▼
    ┌─────────────────────┐
    │  AWS Cognito        │
    │  Store Credential   │
    └─────────────────────┘
```

### Authentication Flow

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
       │ 1. Request Login
       ▼
┌─────────────────────────┐
│  Laravel Application    │
│  - webAuthnLoginOptions │
└──────┬──────────────────┘
       │
       │ 2. Get Challenge & Credentials
       ▼
┌─────────────────────────┐
│  AWS Cognito            │
│  - Generate Challenge   │
│  - List User's Keys     │
└──────┬──────────────────┘
       │
       │ 3. Challenge Options
       ▼
┌───────────────────────────────────┐
│  Client Browser                   │
│  navigator.credentials.get()      │
│  ┌─────────────────────────────┐  │
│  │  WebAuthn API               │  │
│  │  - Request User Verification│  │
│  └────────┬────────────────────┘  │
└───────────┼───────────────────────┘
            │
            │ 4. User Verifies
            │    (Biometric/PIN)
            ▼
    ┌───────────────────┐
    │  Authenticator    │
    │  - Sign Challenge │
    │  - with Private   │
    │    Key            │
    └────────┬──────────┘
             │
             │ 5. Return Assertion
             ▼
    ┌────────────────────────────┐
    │  Client sends Assertion    │
    │  - Credential ID           │
    │  - Signature               │
    │  - Authenticator Data      │
    └────────┬───────────────────┘
             │
             │ 6. Verify Assertion
             ▼
    ┌─────────────────────────────┐
    │  Laravel Application        │
    │  - webAuthnLoginVerify()    │
    └────────┬────────────────────┘
             │
             │ 7. Verify Signature
             ▼
    ┌─────────────────────┐
    │  AWS Cognito        │
    │  - Verify Signature │
    │  - Issue Tokens     │
    └─────────┬───────────┘
              │
              │ 8. Return Tokens
              ▼
    ┌─────────────────────┐
    │  Authenticated!     │
    │  - AccessToken      │
    │  - IdToken          │
    │  - RefreshToken     │
    └─────────────────────┘
```

---

## 4. Component Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                     Client Layer                             │
│  ┌────────────┐  ┌────────────┐  ┌────────────────────────┐  │
│  │  Web UI    │  │  Mobile    │  │  JavaScript/TypeScript │  │
│  │  (Blade)   │  │  App       │  │  - WebAuthn API        │  │
│  └────────────┘  └────────────┘  └────────────────────────┘  │
└────────────────────────┬─────────────────────────────────────┘
                         │
                         │ HTTP/HTTPS
                         ▼
┌──────────────────────────────────────────────────────────────┐
│                  Laravel Application Layer                    │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  Controllers                                         │    │
│  │  - PasswordlessAuthController                        │    │
│  │    + sendOtp() / verifyOtp()                         │    │
│  │    + sendMagicLink() / verifyMagicLink()            │    │
│  │    + webAuthn*()                                     │    │
│  └────────────────────┬─────────────────────────────────┘    │
│                       │                                       │
│  ┌────────────────────▼─────────────────────────────────┐    │
│  │  Traits                                              │    │
│  │  - PasswordlessAuthentication                        │    │
│  │    + initiatePasswordlessAuth()                      │    │
│  │    + verifyPasswordlessChallenge()                   │    │
│  │    + resendPasswordlessCode()                        │    │
│  └────────────────────┬─────────────────────────────────┘    │
│                       │                                       │
│  ┌────────────────────▼─────────────────────────────────┐    │
│  │  Services                                            │    │
│  │  - AwsCognitoClient (Extended)                       │    │
│  │    + adminInitiateAuth()                             │    │
│  │    + adminRespondToAuthChallenge()                   │    │
│  └────────────────────┬─────────────────────────────────┘    │
└─────────────────────────┼───────────────────────────────────┘
                          │
                          │ AWS SDK
                          ▼
┌──────────────────────────────────────────────────────────────┐
│                      AWS Services Layer                       │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  AWS Cognito User Pool                               │    │
│  │  ┌────────────────────────────────────────────────┐  │    │
│  │  │  Lambda Triggers                               │  │    │
│  │  │  - DefineAuthChallenge                         │  │    │
│  │  │  - CreateAuthChallenge                         │  │    │
│  │  │  - VerifyAuthChallengeResponse                 │  │    │
│  │  └────────────────────────────────────────────────┘  │    │
│  └──────────────────────┬───────────────────────────────┘    │
│                         │                                     │
│                    ┌────┴────┬────────────┐                  │
│                    ▼         ▼            ▼                  │
│           ┌─────────┐  ┌─────────┐  ┌──────────┐            │
│           │ AWS SNS │  │ AWS SES │  │ DynamoDB │            │
│           │  (SMS)  │  │ (Email) │  │  (Cache) │            │
│           └─────────┘  └─────────┘  └──────────┘            │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

---

## 5. Data Flow - OTP Authentication

```
Step 1: Initiate Auth
    Client → Laravel → Cognito
    [username] → [adminInitiateAuth] → [DefineAuthChallenge Lambda]
    
Step 2: Generate & Send OTP
    Cognito → CreateAuthChallenge Lambda
    → Generate 6-digit OTP
    → Store in privateChallengeParameters
    → Send via SNS/SES to user
    
Step 3: Return Session
    Cognito → Laravel → Client
    [Session Token + Challenge Info]
    
Step 4: User Submits OTP
    Client → Laravel → Cognito
    [username + OTP + Session] → [adminRespondToAuthChallenge]
    
Step 5: Verify OTP
    Cognito → VerifyAuthChallenge Lambda
    → Compare submitted OTP with stored OTP
    → Return answerCorrect: true/false
    
Step 6: Issue Tokens (if correct)
    Cognito → DefineAuthChallenge Lambda
    → issueTokens: true
    → Generate AccessToken, IdToken, RefreshToken
    
Step 7: Return to Client
    Cognito → Laravel → Client
    [Tokens] → Store in session → Authenticated!
```

---

## 6. Security Layers

```
┌──────────────────────────────────────────────────┐
│           Application Security Layer              │
│  - Rate Limiting (3-5 attempts)                  │
│  - Account Lockout (after max attempts)          │
│  - CSRF Protection                               │
│  - Input Validation                              │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│            Transport Security Layer               │
│  - HTTPS/TLS 1.3                                 │
│  - Secure Headers                                │
│  - Cookie Security (HttpOnly, Secure, SameSite)  │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│          Authentication Security Layer            │
│  - OTP: 6-8 digit codes, 5-15 min expiry        │
│  - Magic Link: 32+ byte tokens, single use       │
│  - WebAuthn: Public key crypto, biometrics       │
│  - Session: Short-lived tokens, refresh rotation │
└────────────────┬─────────────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────────────┐
│             AWS Cognito Security Layer            │
│  - User Pool Encryption at Rest                  │
│  - IAM Role Permissions                          │
│  - CloudWatch Logging                            │
│  - Advanced Security Features (optional)         │
│  - Anomaly Detection                             │
└──────────────────────────────────────────────────┘
```

---

## 7. Migration Path for Existing Users

```
┌─────────────────────────────────────────────────────────┐
│              Existing User (Password-based)              │
└───────────────────────┬─────────────────────────────────┘
                        │
                        │ User Logs In
                        ▼
                ┌───────────────────┐
                │  Login Successful │
                └───────┬───────────┘
                        │
                        ▼
        ┌───────────────────────────────────┐
        │ Show Passwordless Promotion       │
        │ "Enable passwordless login?"      │
        │ [Enable] [Maybe Later] [Never]    │
        └───┬───────────────┬───────────┬───┘
            │               │           │
    ┌───────▼───────┐       │           │
    │ User Enables  │       │           │
    │ Passwordless  │       │           │
    └───────┬───────┘       │           │
            │               │           │
            ▼               ▼           ▼
    ┌────────────────┐  ┌──────┐  ┌─────────┐
    │ Setup Flow     │  │ Skip │  │ Disable │
    │ - Verify Phone │  │      │  │ Forever │
    │   or Email     │  └──────┘  └─────────┘
    │ - Test OTP     │
    │ - Confirm      │
    └────────┬───────┘
             │
             ▼
    ┌──────────────────────────────────┐
    │ Update User Preferences:         │
    │ - passwordless_enabled = true    │
    │ - passwordless_method = 'sms'    │
    └────────┬─────────────────────────┘
             │
             ▼
    ┌────────────────────────────┐
    │  Future Logins:            │
    │  1. Enter username         │
    │  2. Receive OTP            │
    │  3. Enter OTP              │
    │  4. Authenticated!         │
    │                            │
    │  (Password still works as  │
    │   fallback if configured)  │
    └────────────────────────────┘
```

---

## Summary

These architecture diagrams illustrate:

1. **SMS/Email OTP Flow**: Complete flow from initiation to token issuance
2. **Magic Link Flow**: Email-based authentication with secure tokens
3. **WebAuthn Flow**: Modern biometric authentication
4. **Component Architecture**: How different layers interact
5. **Data Flow**: Step-by-step OTP authentication process
6. **Security Layers**: Multiple levels of security controls
7. **Migration Path**: How to transition existing users

All approaches integrate seamlessly with the existing `ellaisys/aws-cognito` package architecture while leveraging AWS Cognito's native capabilities.
