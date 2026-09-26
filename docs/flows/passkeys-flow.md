# Passkeys / Passwordless Flow – Sequence Diagram

Documents passwordless/passkey (WebAuthn / custom-auth-challenge based) authentication interactions,
covering registration of a passkey credential and authentication using it via Cognito's custom auth flow.

> Note: AWS Cognito does not natively support WebAuthn passkeys as of this writing; this flow models the
> package's `CUSTOM_AUTH` challenge-based approach commonly used to implement passwordless/passkey login,
> where a Lambda trigger (Define/Create/Verify Auth Challenge) validates the passkey assertion.

```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant Route as Laravel Route
    participant Controller as PasskeyController<br/>(PasswordlessActions trait)
    participant Validator as Request Validator
    participant CognitoClient as AwsCognitoClient<br/>(Service)
    participant AWS as AWS Cognito<br/>(Custom Auth + Lambda Triggers)
    participant Store as Storage<br/>(Cache/Session/DB)

    rect rgb(235,245,255)
    note over Client,Store: Register Passkey Credential
    Client->>Route: POST /passkey/register/options {email}
    Route->>Controller: getRegistrationOptions(Request)
    Controller->>Validator: validate(email exists, user active)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: generateChallengeOptions(email)
        CognitoClient->>Store: create/store challenge + user handle
        Store-->>CognitoClient: ack
        CognitoClient-->>Controller: WebAuthn creation options
        Controller-->>Client: 200 {publicKeyCredentialCreationOptions}
    end

    Client->>Client: navigator.credentials.create() (device-side)
    Client->>Route: POST /passkey/register/verify {email, attestation}
    Route->>Controller: verifyRegistration(Request)
    Controller->>Validator: validate(attestation payload structure)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: verifyAttestation(email, attestation)
        alt attestation invalid / challenge mismatch
            CognitoClient-->>Controller: throw InvalidUserFieldException
            Controller-->>Client: 400 Bad Request
        else success
            CognitoClient->>AWS: AdminUpdateUserAttributes<br/>(store public key as custom attribute)
            AWS-->>CognitoClient: ack
            CognitoClient->>Store: persist credential_id + public_key
            CognitoClient-->>Controller: success
            Controller-->>Client: 201 {passkey_registered}
        end
    end
    end

    rect rgb(235,255,240)
    note over Client,Store: Passwordless Login - Initiate
    Client->>Route: POST /login/passkey/options {email}
    Route->>Controller: getAuthenticationOptions(Request)
    Controller->>Validator: validate(email exists)
    alt user not found
        Validator-->>Controller: ValidationException / NoLocalUserException
        Controller-->>Client: 404 Not Found
    else success
        Controller->>CognitoClient: initiateCustomAuth(email)
        CognitoClient->>AWS: InitiateAuth (CUSTOM_AUTH)
        AWS->>AWS: DefineAuthChallenge Lambda
        AWS->>AWS: CreateAuthChallenge Lambda<br/>(fetch stored public_key, generate challenge)
        AWS-->>CognitoClient: ChallengeName=CUSTOM_CHALLENGE, Session, PublicChallengeParameters
        CognitoClient-->>Controller: challenge + session
        Controller-->>Client: 200 {publicKeyCredentialRequestOptions, session}
    end
    end

    rect rgb(255,250,235)
    note over Client,Store: Passwordless Login - Verify Assertion
    Client->>Client: navigator.credentials.get() (device-side)
    Client->>Route: POST /login/passkey/verify {email, session, assertion}
    Route->>Controller: verifyAuthentication(Request)
    Controller->>Validator: validate(assertion payload, session present)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: respondToCustomChallenge(session, assertion)
        CognitoClient->>AWS: RespondToAuthChallenge (ANSWER=signature)
        AWS->>AWS: VerifyAuthChallengeResponse Lambda<br/>(validate signature against public_key)
        alt signature invalid
            AWS-->>CognitoClient: NotAuthorizedException
            CognitoClient-->>Controller: throw InvalidUserException
            Controller-->>Client: 401 Unauthorized
        else success
            AWS-->>CognitoClient: AuthenticationResult (tokens)
            CognitoClient->>Store: cache token/session
            CognitoClient-->>Controller: tokens
            Controller-->>Client: 200 {access_token, id_token, refresh_token}
        end
    end
    end

    rect rgb(255,235,235)
    note over Client,Store: Remove Passkey Credential
    Client->>Route: DELETE /passkey/{credential_id} {access_token}
    Route->>Controller: removePasskey(Request, credential_id)
    Controller->>Validator: validate(credential_id belongs to user)
    alt not found / not owned
        Validator-->>Controller: ValidationException
        Controller-->>Client: 404 Not Found
    else success
        Controller->>CognitoClient: removeCredential(accessToken, credentialId)
        CognitoClient->>AWS: AdminUpdateUserAttributes<br/>(clear/remove public_key attribute)
        AWS-->>CognitoClient: ack
        CognitoClient->>Store: delete credential record
        CognitoClient-->>Controller: success
        Controller-->>Client: 200 {passkey_removed}
    end
    end
```

## Notes

- **Validation** covers presence/shape of WebAuthn attestation and assertion payloads before calling AWS/Lambda.
- **Approval step**: attestation/signature verification (via Lambda triggers `CreateAuthChallenge` /
  `VerifyAuthChallengeResponse`) acts as the cryptographic approval gate — no password is ever transmitted.
- **Error handling** separates client input errors (422/400/404) from authentication failures (401) when a
  signature or challenge fails verification.
- **Data store** persists challenge nonces (short-lived) and long-lived credential public keys tied to the user.