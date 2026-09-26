# Device Actions Flow – Sequence Diagram

Documents device tracking/remembering interactions using the `DeviceActions` trait, `AwsCognitoClient`,
and AWS Cognito (list, remember/forget, update status, and confirm device operations).

```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant Route as Laravel Route
    participant Controller as DeviceController<br/>(DeviceActions trait)
    participant Validator as Request Validator
    participant CognitoClient as AwsCognitoClient<br/>(Service)
    participant AWS as AWS Cognito<br/>Identity Provider
    participant Store as Storage<br/>(Cache/Session/DB)

    rect rgb(235, 255, 235)
    note over Client,Store: Confirm New Device (after login)
    Client->>Route: POST /device {access_token, device_key, device_name, device_config}
    Route->>Controller: create(Request)
    Controller->>Validator: validate(device_key required, device_config required)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: confirmDevice(access_token, device_key, device_name, device_config)
        CognitoClient->>AWS: ConfirmDevice
        alt device already exists / error
            AWS-->>CognitoClient: InvalidParameterException
            CognitoClient-->>Controller: throw AwsCognitoException
            Controller-->>Client: 400 Bad Request
        else success
            AWS-->>CognitoClient: UserConfirmationNecessary flag
            CognitoClient->>Store: persist device_key against user
            CognitoClient-->>Controller: confirmation result
            Controller-->>Client: 200 {device_confirmed, user_confirmation_necessary}
        end
    end
    end

    rect rgb(255,255,255)
    note over Client,Store: Remember Device (Skip MFA) If User Confirmation is Required
    Client->>Route: PUT /device/{device_key} {access_token, remembered_status}
    Route->>Controller: update(Request, device_key)
    Controller->>Validator: validate(device_key: string required, remembered_status: boolean)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: updateDeviceStatus(access_token, device_key, rememberedStatus: string)
        CognitoClient->>AWS: UpdateDeviceStatus (remembered/not_remembered)
        alt device not found
            AWS-->>CognitoClient: ResourceNotFoundException
            CognitoClient-->>Controller: throw NoLocalUserException
            Controller-->>Client: 404 Not Found
        else success
            AWS-->>CognitoClient: ack
            CognitoClient->>Store: update device remembered flag
            CognitoClient-->>Controller: success
            Controller-->>Client: 200 {device_status_updated}
        end
    end
    end

    rect rgb(255,255,255)
    note over Client,Store: List Devices for the authenticated user
    Client->>Route: GET /device {access_token}
    Route->>Controller: list(Request)
    Controller->>CognitoClient: listDevices(access_token, limit: 10, pagination_token: null)
    CognitoClient->>AWS: ListDevices
    alt token invalid
        AWS-->>CognitoClient: NotAuthorizedException
        CognitoClient-->>Controller: throw InvalidUserException
        Controller-->>Client: 401 Unauthorized
    else success
        AWS-->>CognitoClient: Devices[]
        CognitoClient->>Store: sync device metadata cache
        CognitoClient-->>Controller: devices list
        Controller-->>Client: 200 {devices: [...]}
    end
    end

    rect rgb(255,255,255)
    note over Client,Store: Get Device Details
    Client->>Route: GET /device/{device_key} {access_token}
    Route->>Controller: getDevice(Request, device_key)
    Controller->>Validator: validate(device_key: string required)
    Controller->>CognitoClient: getDevice(access_token, device_key)
    CognitoClient->>AWS: GetDevice
    alt device not found
        AWS-->>CognitoClient: ResourceNotFoundException
        CognitoClient-->>Controller: throw NoLocalUserException
        Controller-->>Client: 404 Not Found
    else success
        AWS-->>CognitoClient: DeviceAttributes
        CognitoClient-->>Controller: device detail
        Controller-->>Client: 200 {device}
    end
    end

    rect rgb(255,235,235)
    note over Client,Store: Forget / Delete Device
    Client->>Route: DELETE /device/{device_key} {access_token}
    Route->>Controller: delete(Request, device_key)
    Controller->>Validator: validate(device_key: string required)
    alt validation fails
        Validator-->>Controller: ValidationException
        Controller-->>Client: 422 Unprocessable Entity
    else validation passes
        Controller->>CognitoClient: forgetDevice(access_token, device_key)
        CognitoClient->>AWS: ForgetDevice
        alt device not found
            AWS-->>CognitoClient: ResourceNotFoundException
            CognitoClient-->>Controller: throw NoLocalUserException
            Controller-->>Client: 404 Not Found
        else success
            AWS-->>CognitoClient: ack
            CognitoClient->>Store: remove device record
            CognitoClient-->>Controller: success
            Controller-->>Client: 200 {device_removed}
        end
    end
    end
```

## Notes

- **Validation** ensures `device_key`/`device_group_key` are present and correctly formatted before AWS calls.
- **Approval step**: `ConfirmDevice` response's `UserConfirmationNecessary` flag gates whether the client must
  additionally prompt the user to confirm the device (e.g., via email/SMS).
- **Error handling** distinguishes not-found devices (404), invalid/expired tokens (401), and bad input (422/400).
- **Data store** caches device metadata and remembered status locally to reduce repeated `ListDevices`/`GetDevice` calls.