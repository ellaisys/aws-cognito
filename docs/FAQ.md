# **Frequently Asked Questions**

> [!NOTE]
> Last Updated: 2026-07-27

## Does this package support the Cognito Hosted UI?

Yes.

The package supports both **Web Application (Hosted UI)** and **API-based authentication**, allowing you to choose the authentication experience that best suits your application.

* Use the Hosted UI when you want AWS Cognito to manage the authentication experience.
* Use the API endpoints when you want complete control over your application's user interface.


## Does this package support Cognito Identity Pools?

Not directly.

This package is built around **Amazon Cognito User Pools**, which provide authentication and user management.

If your application requires temporary AWS credentials to access AWS services, you can integrate the User Pool tokens issued by this package with an Identity Pool as part of your architecture.


## Does this package support Cognito User Pools?

Yes.

Supporting Amazon Cognito User Pools is the primary purpose of this package. It includes support for the majority of Cognito authentication and user management capabilities, including:

* User registration
* User authentication
* Password management
* Multi-Factor Authentication (MFA)
* Email and SMS verification
* Token refresh
* Device authentication
* OAuth 2.0 / OpenID Connect
* Hosted UI
* Social login
* Passkeys (WebAuthn)

Support continues to evolve alongside new Cognito features.


## Can I build an API-only authentication service?

Yes.

The package is designed to support multiple application architectures, including:

* Laravel web applications
* Single Page Applications (SPA)
* Mobile applications
* REST APIs
* Headless authentication services

You can expose authentication endpoints without using any server-rendered views.


## Can I use Laravel Fortify?

Yes.

Laravel Fortify can be integrated by replacing its default authentication implementation with Amazon Cognito while continuing to use Fortify's authentication routes and features.


## Can I use Laravel Breeze?

Yes.

Laravel Breeze provides the frontend scaffolding, while this package replaces the authentication backend with Amazon Cognito. This lets you keep the familiar Breeze experience without storing passwords locally.


## Which Laravel versions are supported?

The package currently supports:

* Laravel 7
* Laravel 8
* Laravel 9
* Laravel 10
* Laravel 11
* Laravel 12

Support for newer Laravel releases will be added as they become stable.


## Can I migrate existing users?

Yes.

Several migration strategies are supported depending on your requirements:

* Bulk importing users into Amazon Cognito
* Migrating users on their first login using the Cognito User Migration Lambda trigger
* Creating users programmatically through the Cognito APIs

The appropriate approach depends on whether existing password hashes can be migrated or users need to reset their passwords.


## Does this package support Social Login?

No. This will be supported in a future release.

The package shall support any identity provider configured in your Amazon Cognito User Pool, including:

* Google
* Apple
* Facebook
* Login with Amazon
* OpenID Connect (OIDC)
* SAML 2.0 providers

Configuration is performed in Amazon Cognito, while the package manages the authentication flow.


## Does this package support Passkeys?

Yes.

The package supports Amazon Cognito's WebAuthn (Passkey) authentication, allowing users to authenticate using platform authenticators or security keys instead of passwords.

Refer to the [Passkeys Functionality](README_FIDO2.md) for more details on how to enable and use this feature.


## Does this package support Remembered Devices?

Yes.

The package supports Cognito's remembered devices and device authentication features. When device tracking is enabled in your User Pool, trusted devices can participate in Cognito's secure device authentication flow, helping reduce repeated MFA prompts based on your Cognito configuration.

Refer to the [Device Authentication](README_DEVICE_AUTH.md) section for more details on how to enable and use this feature.


## Does this package support Single Sign-On (SSO)?

Yes.

A single Amazon Cognito User Pool can authenticate multiple applications using standard OAuth 2.0 and OpenID Connect (OIDC) flows.

This package makes it easy to build centralized authentication services that can be shared across web applications, APIs, mobile applications, and services developed in different programming languages.

Refer the [Single Sign-On (SSO) section](README_CORE.md#single-sign-on-sso) for more details.


## Does this package support multi-tenancy?

No.

Multi-tenancy is not currently implemented by the package. If your application requires tenant isolation, it must be implemented as part of your application's architecture or through separate Cognito User Pools.


## Can I customize the authentication views?

Yes.

All Blade views published by the package can be customized to match your application's branding and user experience. You may also bypass the provided views entirely and build your own frontend using the package's authentication endpoints.


## Which authentication flows are supported?

The package supports all major Amazon Cognito authentication methods, including:

* Secure Remote Password (SRP)
* Username and Password authentication
* Passkeys (WebAuthn)
* Device Authentication
* Multi-Factor Authentication (MFA)
* OAuth 2.0 / OpenID Connect authentication

This allows you to choose the authentication flow that best fits your application's security and user experience requirements.
