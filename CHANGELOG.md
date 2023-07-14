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