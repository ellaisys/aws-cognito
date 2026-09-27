# Passkeys / Passwordless Flow – Sequence Diagram

Documents passwordless/passkey (WebAuthn / custom-auth-challenge based) authentication interactions,
covering registration of a passkey credential and authentication using it via Cognito's custom auth flow.


```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant Route as Laravel Route
    participant Controller as WebAuthPasskeyController<br/>(WebAuthPasskey trait)
    participant Validator as Request Validator
    participant CognitoClient as AwsCognitoClient<br/>(ManagePasskeyWebAuthnAction trait)
    participant AWS as AWS Cognito<br/>Identity Provider
    participant Store as Storage<br/>(Cache/Session/DB)

    rect rgb(235, 255, 235)
    note over Client,Store: Register Passkey (WebAuthn)
    Client->>Route: POST /passkey/start {access_token}
    Route->>Controller: start(Request)
    Controller->>Validator: validate(access_token exists, user active)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: startWebAuthnRegistration(access_token)
        CognitoClient->>AWS: StartWebAuthnRegistration
        alt attestation invalid / challenge mismatch
            CognitoClient-->>Controller: throw InvalidUserFieldException
            Controller-->>Client: 400 Bad Request
        else success
            AWS-->>CognitoClient: ack
            CognitoClient-->>Controller: ack
            Controller-->>Client: 200 {publicKeyCredentialCreationOptions}
        end
    end

    Client->>Client: navigator.credentials.create() (device-side)

    Client->>Route: POST /passkey/complete {access_token, credential}
    Route->>Controller: complete(Request)
    Controller->>Validator: validate(credential payload structure)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: completeWebAuthnRegistration(access_token, credential)
        CognitoClient->>AWS: CompleteWebAuthnRegistration
        alt error
            CognitoClient-->>Controller: throw InvalidUserFieldException
            Controller-->>Client: 400 Bad Request
        else success
            AWS-->>CognitoClient: ack
            CognitoClient->>Store: persist is_webauthn_enabled true
            CognitoClient-->>Controller: success
            Controller-->>Client: 201 {passkey_registered}
        end
    end
    end

    rect rgb(255,255,255)
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
    Client->>Route: DELETE /passkey/delete {access_token, credential_id}
    Route->>Controller: delete(Request, credential_id)
    Controller->>Validator: validate(credential_id required)
    alt not found / not owned
        Validator-->>Controller: ValidationException
        Controller-->>Client: 404 Not Found
    else success
        Controller->>CognitoClient: deleteWebAuthnCredential(access_token, credential_id)
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