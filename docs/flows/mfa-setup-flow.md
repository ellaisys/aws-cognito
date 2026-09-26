# MFA Setup Flow – Sequence Diagram

Documents the interaction for enabling, verifying, and challenging Multi-Factor Authentication
(Software Token / SMS MFA) using the `MFAActions` trait, `AwsCognitoClient`, and AWS Cognito.

```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant Route as Laravel Route
    participant Controller as MFAController<br/>(MFAActions trait)
    participant Validator as Request Validator
    participant CognitoClient as AwsCognitoClient<br/>(Service)
    participant AWS as AWS Cognito<br/>Identity Provider
    participant Store as Storage<br/>(Cache/Session/DB)

    rect rgb(235,245,255)
    note over Client,Store: Associate Software Token (Get Secret Code)
    Client->>Route: POST /mfa/associate {access_token}
    Route->>Controller: associateSoftwareToken(Request)
    Controller->>Validator: validate(access_token present)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: associateSoftwareToken(accessToken)
        CognitoClient->>AWS: AssociateSoftwareToken
        alt token/session invalid
            AWS-->>CognitoClient: NotAuthorizedException
            CognitoClient-->>Controller: throw InvalidUserException
            Controller-->>Client: 401 Unauthorized
        else success
            AWS-->>CognitoClient: SecretCode
            CognitoClient-->>Controller: secretCode
            Controller-->>Client: 200 {secret_code, qr_uri}
        end
    end
    end

    rect rgb(235,255,240)
    note over Client,Store: Verify Software Token (Enable MFA)
    Client->>Route: POST /mfa/verify {access_token, user_code}
    Route->>Controller: verifySoftwareToken(Request)
    Controller->>Validator: validate(user_code: digits, size)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: verifySoftwareToken(accessToken, userCode)
        CognitoClient->>AWS: VerifySoftwareToken
        alt code mismatch
            AWS-->>CognitoClient: EnableSoftwareTokenMFAException / CodeMismatchException
            CognitoClient-->>Controller: throw InvalidUserFieldException
            Controller-->>Client: 400 Bad Request
        else success
            AWS-->>CognitoClient: Status: SUCCESS
            CognitoClient->>AWS: SetUserMFAPreference (SOFTWARE_TOKEN_MFA=Enabled)
            AWS-->>CognitoClient: ack
            CognitoClient->>Store: update user mfa_enabled flag
            CognitoClient-->>Controller: success
            Controller-->>Client: 200 {mfa_enabled: true}
        end
    end
    end

    rect rgb(255,250,235)
    note over Client,Store: MFA Challenge During Login
    Client->>Route: POST /login/mfa-challenge {session, code}
    Route->>Controller: respondToMFAChallenge(Request)
    Controller->>Validator: validate(session, code required)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: respondToMFAChallenge(session, code)
        CognitoClient->>AWS: RespondToAuthChallenge (SOFTWARE_TOKEN_MFA)
        alt invalid code
            AWS-->>CognitoClient: CodeMismatchException
            CognitoClient-->>Controller: throw InvalidUserFieldException
            Controller-->>Client: 400 Bad Request
        else expired session
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
    note over Client,Store: Disable MFA
    Client->>Route: POST /mfa/disable {access_token}
    Route->>Controller: setMFAPreference(Request)
    Controller->>CognitoClient: setUserMFAPreference(accessToken, disabled)
    CognitoClient->>AWS: SetUserMFAPreference (Enabled=false)
    alt error
        AWS-->>CognitoClient: NotAuthorizedException
        CognitoClient-->>Controller: throw InvalidUserException
        Controller-->>Client: 401 Unauthorized
    else success
        AWS-->>CognitoClient: ack
        CognitoClient->>Store: update user mfa_enabled flag
        Controller-->>Client: 200 {mfa_enabled: false}
    end
    end
```

## Notes

- **Validation** ensures `user_code` follows the TOTP format (6 digits) before hitting AWS.
- **Approval step**: `VerifySoftwareToken` returning `SUCCESS` acts as the approval gate before enabling MFA preference.
- **Error handling**: distinguishes between code mismatch (400) and session/token issues (401).
- **Data store** tracks the local `mfa_enabled` flag to avoid unnecessary AWS calls on subsequent checks.