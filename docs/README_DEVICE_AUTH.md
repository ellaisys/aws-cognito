# **Device Authentication**

## **Overview**

With Amazon Cognito user pools, you can associate each of your users' devices with a unique device identifier: a device key. When you present the device key and perform device authentication at sign-in, you can configure your application with a trusted device authentication flow. Device authentication is a security feature that allows users to register and authenticate their devices with AWS Cognito. This feature enhances security by enabling multi-factor authentication (MFA) and device tracking, ensuring that only trusted devices can access user accounts. 

When a user logs in from a new device, they may be prompted to register the new device. Once registered, the device can be remembered for future logins, reducing the need for repeated MFA prompts. This feature is particularly useful for applications that require high security, such as banking or healthcare apps.

The device authentication process involves using logic similar to the SRP (Secure Remote Password) protocol, which allows for secure authentication without transmitting the user's device credentials. Instead, cryptographic operations are performed based on the device's unique identifier and the user's credentials. This ensures that sensitive information is never exposed over the network.

This document explains how you can use this in the context of AWS Cognito and Laravel package.

## **Configuration**

Configure your user pool to remember devices in the Sign-in menu of your user pool, under Device tracking as shown below:
<img src="../assets/images/aws_cognito_device_flow1.png" width="100%" alt="cognito device flow"/>

You can choose to always remember devices, or only remember them when the user opts in during sign-in. This setting allows you to control how devices are registered and remembered for future authentication attempts.
<img src="../assets/images/aws_cognito_device_flow2.png" width="100%" alt="cognito device flow"/>

For more information on configuring device authentication in AWS Cognito, refer to the [AWS Cognito Documentation](https://docs.aws.amazon.com/cognito/latest/developerguide/amazon-cognito-user-pools-device-tracking.html).

## **Registering a Device**

When a user logs in from a new device, they will be prompted to register the device. The registration process involves generating a unique device key and associating it with the user's account. This claim data is provided with additional **NewDeviceMetadata** having DeviceGroupKey and DeviceKey.

Generate a new SRP secret for your user's device and store it securely on the client side (e.g., in local storage or secure storage). This secret will be used for future device authentication attempts.

This secret is generated using the SRP protocol and is unique to the device. It should be treated as sensitive information and not shared or transmitted over insecure channels.

This package provides a new component view **cognito-device-auth** which can be used to register a new device. The view is located at **resources/views/components/device/main.blade.php** within the package. You may not require to customize this view, but if you wish to do so, you can publish the package views and modify the view as needed.

To use this view in your application, you can include it in your Blade templates as follows:

```blade
  @section('content')
  <x-cognito-device-auth />
  ...
  ...
  ...

  @stack('cognito-device-auth-scripts')
  @endsection

```
Add the @stack('cognito-device-auth-scripts') directive to your main layout file (e.g., home.blade.php) to ensure that the necessary JavaScript for device authentication is included in your application. This should be added after the @stack directives for other Cognito-related scripts to ensure proper loading order.

for the API based implementation, exposes CRUD for you can use. The following endpoints are available for device authentication:

```php

  GET|HEAD  api/cognito/device ...................... Ellaisys\Cognito\Http\Controllers\Auth\DeviceController@list
  POST      api/cognito/device .................... Ellaisys\Cognito\Http\Controllers\Auth\DeviceController@create
  PUT       api/cognito/device/{deviceKey} ........ Ellaisys\Cognito\Http\Controllers\Auth\DeviceController@update
  DELETE    api/cognito/device/{deviceKey} ........ Ellaisys\Cognito\Http\Controllers\Auth\DeviceController@delete

```

## **Device Authentication Flow**

For this package, a new service is provided **Ellaisys\Cognito\Services\AwsCognitoSrpService** which implements the Device authentication flow. The flow consists of the following methods:
1. *generateEphemeral* - Generates the SRP_A value on the client side, along with the private ephemeral value 'a'.
2. *processChallenge* - Builds the response to the DEVICE_PASSWORD_VERIFIER challenge using the SRP_B, salt, and secret block received from the server.

### **Step 1: SRP_A Generation**

The package expects the client (browser/mobile app) to compute the SRP_A value before sending it to the server. This is a critical part of the SRP protocol, as it ensures that the actual password is never transmitted. However, for ease you can have the package compute SRP_A on the server side as well, but this is not recommended for security reasons.
**IMPORTANT: SRP_A is calculated BEFORE receiving the salt from the server.**

The SRP_A value is generated **on the client side** (browser/mobile app) using the following mathematical operation:

$$SRP\_A = g^a \bmod N$$

Where:
- **a** = A cryptographically secure random integer generated by the client
- **g** = Generator (provided by AWS Cognito)
- **N** = Large prime modulus (provided by AWS Cognito)
- **mod** = Modulo operation

### **Step 2: Initiate Authentication**

The client sends the following to the server:

```
POST /login/auth-challenge
Content-Type: application/json

{
  "challenge_name": "DEVICE_SRP_AUTH",
  "session": "<session_token_from_the_server>",
  "username": "<username_for_srp>",
  "challenge_value": "<computed_challenge_value>",
  "challenge_params": "<returned_challenge_params_from_the_server>"
}
```

The `srp_a` field here contains the **pre-computed SRP_A value** (not the actual user password). if you choose not to compute SRP_A on the client side, you can omit this field and the package will compute it for you on the server side. 

However, for **higher security**, it is recommended to compute SRP_A on the client side and send it to the server. If you choose to compute the SRP_A value on the client side, make sure to use a secure random number generator for calculating the private ephemeral value `a` as per the SRP protocol specifications.

Store the private ephemeral value `a` securely on the client side (e.g., in memory) and do not transmit it to the server. Share a unique session token (e.g., a UUID) with the server so that responses from the server can be correlated with the correct authentication session. This session value is can be used to recover the private ephemeral value `a` on the client side when processing the server's challenge response in the next step.

Example:
1. Client generates a private ephemeral value `a` and computes `SRP_A` using the formula above.
2. Client also generates a unique session token (e.g., UUID) to correlate the authentication session.
3. Client stores the session token and the private ephemeral value `a` securely on the client side (e.g., in memory) as a key-value pair, where the session token is the key and the private ephemeral value `a` is the value.
4. Client sends the username, computed `SRP_A`, and session token to the server.
5. The server responds with the authentication challenge, which includes the salt, secret block, and SRP_B values needed for the next step of the authentication process. The server also includes the same session token in its response so that the client can correlate the response with the correct authentication session and retrieve the corresponding private ephemeral value `a` for processing the challenge response.
6. The client uses the session token to retrieve the private ephemeral value `a` from memory and processes the server's challenge response to compute the password proof, which is then sent back to the server for verification.
7. The client also sends the private ephemeral value `a` back to the now as a session value in the next step when responding to the server's challenge, so that the server can use it to verify the password proof and authenticate the user.

### **Step 3: Server-Side Processing**

The server receives the request and calls AWS Cognito's endpoint. AWS Cognito processes the SRP_A value and responds with a challenge that includes the following parameters:

AWS Cognito responds with:
- **SRP_B**: Server's SRP value
- **Salt**: Used in password hashing
- **SecretBlock**: Encrypted information from the server
- **UserIdForSrp**: User identifier for SRP calculations
- **Session**: Session token for the ongoing authentication process
- **ChallengeName**: Typically "PASSWORD_VERIFIER" indicating the next step in the authentication process

### **Step 4: Password Proof Calculation**

Generate the password hash (with SHA256 encryption) using the pool name (without region), username and the user's password. You can use the following formula to calculate the password proof:

```javascript

    // Set the actual password value in the hidden challenge value input
    let passKey = atob(poolName) + username+ ':' + password;

    // Hash with SHA256 and set the hashed value in the challenge value input
    let passKeyHash = await hashEncrypt(passKey, 'SHA-256');
```

Send that value back to the server in response to the challenge with **PASSKEY_HASH** as the key.

### **Step 5: Respond to the Auth Challenge**

The client sends the following to the server:

```
POST /login/auth-challenge
Content-Type: application/json

{
  "challenge_name": "PASSWORD_VERIFIER",
  "session": "<session_token_as_private_ephemeral_value_a>",
  "username": "<username_for_srp>",
  "challenge_value": "<computed_challenge_value>"
}
```

The `challenge_value` field contains the stringified JSON object with the following structure. Do not change the keys or the case as they are expected by the server for calculating the **PASSWORD_CLAIM_SIGNATURE** and authenticating the user:

```
{
  "SALT": "<salt_from_step-2>",
  "SECRET_BLOCK": "<secret_block_from_step-2>",
  "SRP_B": "<SRP_B_from_step-2>",
  "USER_ID_FOR_SRP": "<username_for_srp_from_step-2>",
  "PASSKEY_HASH": "<computed_password_proof_hash>"
}
```

The server side, the package will process this challenge response and call AWS Cognito's endpoint to verify the password proof. If the proof is correct, AWS Cognito will authenticate the user and return an authentication token.

## **Key Points:**
- **Phase 1 (calculateSrpA)**: Uses only `N` and `g`. Generates a random `a`. NO password, username, or salt needed.
- **Phase 2 (calculatePasswordProof)**: Uses `salt`, `password`, and `username` received from server
- **a** must be generated using cryptographically secure random number generator
- **N** and **g** are negotiated during the initial handshake with AWS Cognito
- The values must be converted to appropriate formats (hex, base64) for transmission
- SRP_A is typically a very large number (1024-bit to 2048-bit range)

## **Understanding SRP Parameters: N and g**

### **What is N (Modulus)?**

**N** is a very large **prime number** used in the SRP protocol:
- **Bit Size**: Typically 1024-bit or 2048-bit (AWS Cognito uses 1024-bit)
- **Purpose**: Defines the size of the SRP group and ensures security
- **Used in**: The modular exponentiation formula: **SRP_A = g^a mod N**
- **Fixed Value**: The same N is used for all authentications in a given Cognito User Pool

Example (simplified):
```
N = 2^1024 - 2^960 - 1 + 2^64 * floor(2^894 * pi + 129093)
```

### **What is g (Generator)?**

**g** is a small **generator** (primitive root) of the multiplicative group modulo N:
- **Typical Value**: Usually **2** or **5**
- **Purpose**: Used to generate SRP_A and SRP_B values
- **Used in**: The modular exponentiation with random integers
- **Fixed Value**: Same g is used for all authentications in a given system

Example:
```
g = 2
```

### **How to Obtain N and g**

In AWS Cognito SRP authentication, **N and g are provided by the Cognito server** automatically. However, you don't need to **manually fetch or calculate N and g** - the library handles this automatically!

### **SRP Group Standards**

**RFC 2409 - SRP Group 1 (1024-bit):**
```
N = 2^1024 - 2^960 - 1 + 2^64 * floor(2^894 * pi + 129093)
g = 2
```

**RFC 3526 - SRP Group 14 (2048-bit):**
```
N = 2^2048 - 2^1984 - 1 + 2^64 * floor(2^1918 * pi + 124476)
g = 2
```

AWS Cognito typically uses **RFC 2409 (1024-bit) with g=2**.

### **Parameter Summary Table**

| Parameter | What It Is | Where It Comes From | Typical Size | Used In |
|-----------|-----------|-------------------|--------------|---------|
| **N** | Large prime modulus | AWS Cognito server | 1024 or 2048 bits | Modulo operation |
| **g** | Generator (primitive root) | AWS Cognito server | Small integer (2 or 5) | Exponentiation |
| **a** | Secret random integer | Generated by client | 128-256 bits | SRP_A calculation |
| **SRP_A** | g^a mod N | Calculated by client | Same as N (1024 or 2048 bits) | Sent to server |

### **Why These Parameters Matter**

1. **Security**: Larger N (2048-bit) provides better security than smaller N (1024-bit)
2. **Authentication**: Different N and g values define different SRP groups; both parties must use the same group
3. **Performance**: Larger N means longer calculation time for modular exponentiation
4. **Standardization**: RFC standards ensure interoperability between different implementations


## **References**

- [AWS Cognito Authentication Flow Documentation](https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminInitiateAuth.html)
- [SRP Protocol Specification (RFC 2945)](https://tools.ietf.org/html/rfc2945)
- [SRP Protocol Version 6 (RFC 5054)](https://tools.ietf.org/html/rfc5054)
- [Amazon Cognito Identity JS GitHub](https://github.com/aws-amplify/amplify-js/tree/main/packages/amazon-cognito-identity-js)

