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