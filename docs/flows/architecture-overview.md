# Overall Architecture – Component & Flow Diagram

This diagram shows the high-level architecture of the `ellaisys/aws-cognito` Laravel package: how HTTP
requests flow through routes, controllers/traits, guards, the core service, and out to AWS Cognito and
local storage — tying together the individual flows documented in this folder.

## Component Diagram

```mermaid
flowchart TB
    subgraph ClientLayer["Client Layer"]
        Browser["Browser / Mobile App / API Consumer"]
    end

    subgraph LaravelApp["Laravel Application"]
        subgraph RoutesLayer["Routes"]
            R1["auth routes<br/>(login, register, refresh, logout)"]
            R2["password routes<br/>(forgot, reset)"]
            R3["mfa routes"]
            R4["device routes"]
            R5["passkey routes"]
            R6["admin routes"]
        end

        subgraph ControllerLayer["Controllers / Traits"]
            C1["AuthenticatesUsers"]
            C2["PasswordActions"]
            C3["MFAActions"]
            C4["DeviceActions"]
            C5["PasswordlessActions"]
            C6["AdminActions"]
        end

        subgraph GuardLayer["Auth Guards"]
            G1["CognitoGuard"]
            G2["CognitoTokenGuard"]
            G3["CognitoSessionGuard"]
        end

        subgraph ServiceLayer["Application Services"]
            S1["AwsCognitoClient"]
            S2["Cognito<br/>(facade / manager)"]
            S3["EncryptionTypes<br/>(SRP / secret hash helpers)"]
        end

        subgraph SupportLayer["Support / Contracts"]
            X1["Custom Exceptions<br/>(InvalidUserException,<br/>NoLocalUserException,<br/>AwsCognitoException, etc.)"]
            X2["StorageInterface<br/>(Cache/Session)"]
        end

        subgraph DataLayer["Data Store"]
            D1[("Local users table")]
            D2[("Cache / Session<br/>token & device store")]
        end
    end

    subgraph AWSLayer["AWS Cloud"]
        A1["AWS Cognito<br/>User Pool"]
        A2["Lambda Triggers<br/>(Define/Create/Verify<br/>Auth Challenge)"]
    end

    Browser -->|HTTP requests| R1
    Browser --> R2
    Browser --> R3
    Browser --> R4
    Browser --> R5
    Browser --> R6

    R1 --> C1
    R2 --> C2
    R3 --> C3
    R4 --> C4
    R5 --> C5
    R6 --> C6

    C1 --> G1
    C1 --> S1
    C2 --> S1
    C3 --> S1
    C4 --> S1
    C5 --> S1
    C6 --> S1

    G1 --> S1
    G2 --> S1
    G3 --> S1
    G1 --> X2
    G2 --> X2

    S1 --> S2
    S1 --> S3
    S1 -->|throws on error| X1
    S1 <--> A1
    A1 <--> A2

    X2 --> D2
    S1 --> D1
    S1 --> D2

    style ClientLayer fill:#eef6ff,stroke:#5b9bd5
    style RoutesLayer fill:#eaffea,stroke:#5cb85c
    style ControllerLayer fill:#fff8e1,stroke:#f0ad4e
    style GuardLayer fill:#f3e8ff,stroke:#9b59b6
    style ServiceLayer fill:#ffe8e8,stroke:#d9534f
    style SupportLayer fill:#f0f0f0,stroke:#777
    style DataLayer fill:#e0f7fa,stroke:#00acc1
    style AWSLayer fill:#fff3e0,stroke:#ff9800
```

## Request Lifecycle Sequence (Cross-Cutting)

A simplified, cross-cutting view of how a typical authenticated request flows through the layers,
independent of the specific feature (login, MFA, device, passkey, admin).

```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant Route as Route
    participant Middleware as Auth Middleware<br/>(cognito guard)
    participant Controller as Controller/Trait
    participant Validator as Validator
    participant Guard as CognitoGuard/<br/>TokenGuard
    participant Service as AwsCognitoClient
    participant AWS as AWS Cognito
    participant Store as Cache/Session/DB

    Client->>Route: HTTP Request (+ Bearer token, if protected)
    Route->>Middleware: resolve guard
    alt protected route & token missing/invalid
        Middleware->>Guard: authenticate()
        Guard->>Store: lookup cached token
        Store-->>Guard: not found / expired
        Guard-->>Middleware: fail
        Middleware-->>Client: 401 Unauthorized
    else authorized or public route
        Middleware-->>Route: pass
        Route->>Controller: dispatch(Request)
        Controller->>Validator: validate(input)
        alt validation fails
            Validator-->>Controller: ValidationException
            Controller-->>Client: 422 Unprocessable Entity
        else validation passes
            Controller->>Service: perform business action
            Service->>AWS: Cognito API call
            alt AWS error
                AWS-->>Service: SDK Exception
                Service-->>Controller: package Exception
                Controller-->>Client: mapped HTTP error (400/401/404/409)
            else success
                AWS-->>Service: result
                Service->>Store: read/write cache/session/local DB
                Store-->>Service: ack
                Service-->>Controller: result DTO
                Controller-->>Client: 200/201 success response
            end
        end
    end
```

## Notes

- **Routes** are grouped by feature (auth, password, MFA, device, passkey, admin) and map to controller
  methods that use the package's auth traits.
- **Controllers/Traits** handle HTTP concerns (validation, response shaping) and delegate all Cognito
  interaction to the **`AwsCognitoClient`** service — the single integration point with AWS.
- **Guards** (`CognitoGuard`, `CognitoTokenGuard`, `CognitoSessionGuard`) plug into Laravel's auth system to
  resolve the authenticated user from a token/session, backed by the storage layer.
- **AwsCognitoClient** wraps the AWS SDK's `CognitoIdentityProvider` client, handles SRP/secret-hash
  computations via `EncryptionTypes`, and translates AWS SDK exceptions into package-specific exceptions.
- **Data Store** spans the local `users` table (mirrors Cognito user records) and cache/session storage
  (access/refresh tokens, device metadata, MFA/passkey challenge state).
- **AWS Cognito** is the identity source of truth, with optional **Lambda triggers** enabling advanced flows
  like custom-auth-based passwordless/passkey login.
- Individual feature flows (login, registration, MFA, devices, passkeys, admin ops) are detailed in their
  respective files in this `docs/flows` folder; this document ties them together at the architecture level.