Release 42 (tag v1.4.0)
 - Feat: Support for Laravel 11 and Laravel 12
 - Feat: Add registration type (invite, register) flow
 - Feat: Update the user email verification at first login flow
 - Fix: Issue [#109](https://github.com/ellaisys/aws-cognito/issues/109)
 - Fix: Issue [#110](https://github.com/ellaisys/aws-cognito/issues/110)
 - Fix: Issue [#113](https://github.com/ellaisys/aws-cognito/issues/113)

Release 41 (tag v1.3.1)
 - Fix: Issue #114, Crash in CognitoTokenGuard for NEW_PASSWORD_REQUIRED challenge
 
Release 40 (tag v1.3.0)
 - Feat: Issue #50, Architecture change to map the local and cognito users with sub (SubjectId)
 - Fix: Issue #86, SSO enabled the user is now created for both guards
 - Fix: Code optimization 

Release 39 (tag v1.2.5)
 - Fix: AWS JWT Token validation timeout
 - Fix: Non declared variable references
 - Fix: Sonar cloud code compliance

Release 38 (tag v1.2.4)
 - Feat: AWS JWT Token validation

Release 37 (tag v1.2.3)
 - Fix: Update the QR library for MFA. The Google Fonts library was depricated.

Release 36 (tag v1.2.2)
 - Fix: Password validation for special characters

Release 35 (tag v1.2.1)
 - Fix: Issue #81 (Anonymous migrations issue in laravel)

Release 34 (tag v1.2.0)
 - Feat: Add sub (cognito uuid) column to user table, and fill during registration.
 - Feat: Provision for user defined passwords.
 - Feat: Password validation based on Cognito Configuration

Release 33 (tag v1.1.6)
 - Fix: Issue #74

Release 32 (tag v1.1.5)
 - Fix: Issue #67
 - Minor updates to code document and removal of log statement

Release 31 (tag v1.1.4)
 - Minor updates to code document and removal of log statement

Release 30 (tag v1.1.3)
 - Fix composer alias to 1.0-dev

Release 29 (tag v1.1.2)
 - Fix composer alias 
 - Doc: Update Readme
 
 Release 28 (tag v1.1.1)
 - Feature: MFA implementation with Software Token and SMS
 
 Release 27 (tag v1.1.0)
 - Feature: MFA implementation with Software Token

Release 26 (tag v1.0.11)
 - Feature: Forced signout with RefreshToken revoked.

Release 25 (tag v1.0.10)
 - Feature: Sign Out / Logout of the Access Token from AWS Cognito
 - Feature: Refresh Token method added to the API storage

Release 24 (tag v1.0.9)
 - Fix: Issue 49 (error with reset passwords expecting json value)

Release 23 (tag v1.0.8):
 - Feature: Added the cognito claim (AccessToken and RefreshToken) to the session parameter.
 
Release 22 (tag v1.0.7):
 - Feature: Add user to the Cognito Group
 - Feature: Get all the user's groups in cognito
 - Fix: Exception handling of the local user creation in Laravel 9.x
 - Fix: No Token Exception at Web Login

Release 21 (tag v1.0.6): 
 - Fix: Issue 28 (security issue in middleware)

Release 20 (tag v1.0.5):
 - Feature: Support for Cognito configuration, where Client Secret is disabled
 - Feature: New User email suppress feature using message action configuration
 - Feature: New User auto verification of email address is made configurable
 - Fix: Modified the exception handling for authentication to show AWS Cognito errors
 - Fix: Exception handling in forgot password for non-cognito users 

Release 19 (tag v1.0.4): 
 - Feature: Forgot password RESEND option
 