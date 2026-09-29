# Passkeys / Passwordless Flow – Sequence Diagram

Documents passwordless/passkey (WebAuthn / user-auth-challenge based) authentication interactions,
covering registration of a passkey credential and authentication using it via Cognito's user-auth-challenge flow. For more details refer [README FIDO2](../README_FIDO2.md)


```mermaid
---
displayMode: compact
config:
  theme: default
  look: classic
---
sequenceDiagram
    autonumber
    actor Client
    participant Route as Laravel Route
    participant Controller as WebAuthPasskeyController<br/>(WebAuthPasskey trait)
    participant CognitoClient as AwsCognitoClient<br/>(ManagePasskeyWebAuthnAction trait)
    participant AWS as AWS Cognito<br/>Identity Provider
    participant Store as Storage<br/>(Cache/Session/DB)

    rect rgb(235, 255, 235)
    note over Client,Store: Register Passkey (WebAuthn)
    Client->>Route: POST /passkey/start {access_token}
    Route->>Controller: start(Request)
    Controller->>CognitoClient: startWebAuthnRegistration(access_token)
    CognitoClient->>AWS: StartWebAuthnRegistration
    AWS-->>CognitoClient: success
    CognitoClient-->>Controller: response
    Controller-->>Client: 200 {publicKeyCredentialCreationOptions}
    Client->>Client: navigator.credentials.create() (device-side)
    Note right of Client: Web Brower returns credential<br/>PasskeyWebAuthn Component
    Client->>Route: POST /passkey/complete {access_token, credential}
    Route->>Controller: complete(Request)
    Controller->>CognitoClient: completeWebAuthnRegistration(access_token, credential)
    CognitoClient->>AWS: CompleteWebAuthnRegistration
    AWS-->>CognitoClient: success
    CognitoClient-->>Controller: success
    Controller->>Store: update is_webauthn_enabled: true
    Controller-->>Client: 200 {passkey_registered}
    end

    rect rgb(255,255,255)
    note over Client,Store: Passwordless Login - WebAuthn
    Client->>Route: GET /login/passkey/challenge {username}
    Route->>Controller: challenge(Request)
    Controller->>AuthGuard: attempt(Request, username, param, CognitoAuthFlowTypes: USER_AUTH)
    AuthGuard->>CognitoClient: authentication
    CognitoClient->>AWS: InitiateAuth (USER_AUTH)
    AWS-->>CognitoClient: response (Challenge, Session, Challenge Parameters)
    CognitoClient-->>Controller: success
    Controller-->>Client: 200 {Challenge, Session, Challenge Parameters}
    Client->>Client: navigator.credentials.get() (device-side)
    Note right of Client: Call navigator.credentials.get() <br/>Call the login/challenge endpoint with the assertion (POST /login/challenge)<br/>to complete the authentication process.
    end

    rect rgb(255,235,235)
    note over Client,Store: Remove Passkey Credential
    Client->>Route: DELETE /passkey/delete {access_token, credential_id}
    Route->>Controller: delete(Request, credential_id)
    Controller->>CognitoClient: deleteWebAuthnCredential(access_token, credential_id)
    CognitoClient->>AWS: DeleteWebAuthnCredential(credential_id)
    AWS-->>CognitoClient: success
    CognitoClient-->>Controller: success
    Controller->>Store: update is_webauthn_enabled: false
    Controller-->>Client: 200 {passkey_removed}
    Client->>Client: PublicKeyCredential.signalUnknownCredential() (device-side)
    Note right of Client: Web Brower deletes credential<br/>PasskeyWebAuthn Component
    end
```

## Notes

- **Validation** covers presence/shape of WebAuthn attestation and assertion payloads before calling AWS/Lambda.
- **Approval step**: attestation/signature verification (via Lambda triggers `CreateAuthChallenge` /
  `VerifyAuthChallengeResponse`) acts as the cryptographic approval gate — no password is ever transmitted.
- **Error handling** separates client input errors (422/400/404) from authentication failures (401) when a
  signature or challenge fails verification.
- **Data store** persists challenge nonces (short-lived) and long-lived credential public keys tied to the user.