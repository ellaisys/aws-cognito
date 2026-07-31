# Features

> [!NOTE]
> Last Updated: <!-- AUTO:last_updated -->2026-07-27<!-- /AUTO:last_updated -->

This document provides a list of features provided by this package. It is not a complete list of features provided by AWS Cognito. For a complete list of features, please refer individual sections of this document.


### Functional

- [Registering Users OR Sign Up](README_CORE.md#registering-users-or-sign-up)
    + [Self Registration](README_CORE.md#self-registration)
    + [Verification of User / Confirm Sign Up](README_CORE.md#verification-of-user)

- [User Invitation OR Invite User](README_CORE.md#user-invitation-or-invite-user)
- [User Authentication OR Sign In](README_CORE.md#user-authentication-or-sign-in)
- [Log Out OR Sign out](README_CORE.md#log-out-or-sign-out)
    + [Sign out and remove access tokens](README_CORE.md#sign-out-and-remove-access-tokens)

- [Forgot Password](README_CORE.md#forgot-password)
- [Refresh Token](README_CORE.md#refresh-token)
- [Delete User](README_CORE.md#delete-user)
- [Single Sign-On (SSO)](README_CORE.md#single-sign-on)
- [Password Validation](README_CORE.md#password-validation)
- [Token Validation](README_CORE.md#token-validation)
- [Multi-Factor Authentication (MFA)](./docs/README_MFA.md)
- [FIDO2 Security Keys Passkey](./docs/README_FIDO2.md)
- [SRP Authentication](./docs/README_SRP.md)
- [Device Authentication](./docs/README_DEVICE_AUTH.md)


### Technical

- Easy API Token handling (uses the cache driver). Refer [Changes in Auth Configurations](README_CONFIG.md#changes-in-auth-configurations)
- DynamoDB support for Web Sessions and API Tokens. It is useful for server redundency OR multiple containers. Refer [Session Storage Configurations](README_CONFIG.md#dynamodb-storage)
- Support for App Client without Secret
- Support for Cognito Groups, including assigning a default group to a new user
- Session (Web) now has AccessToken and RefreshToken as part of the claim object
- [Password validation based on Cognito Configuration](#password-validation-based-of-cognito-configuration)
- [Mapping Cognito User using Subject UUID](#mapping-cognito-user-using-subject-uuid)
- [Preconfigured routes and controllers for Web and API ](README_ROUTES.md)
- [Preconfigured views for Web ](README_ROUTES.md#web-views-and-components)


## What this package provides

1. A simple way to integrate AWS Cognito into your Laravel application.
2. Preconfigured set of routes, controllers, and views for Web and API.
3. You don't need to create an extra field to store the verification token.
4. You don't have to bother about the Sessions or API tokens, they are managed for you. The session or token is managed via the standard mechanism of Laravel. You have the liberty to keep it where ever you want, no security loop holes.