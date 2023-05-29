## **MFA Functionality**
The library currently provides only the MFA for the Software Token. The SMS based TOPT is still under development and not implemented.

## **Configurations**


## **Features**
- [Login (MFA Enabled)](#login)
- [Activate MFA](#activate-mfa)
- [Verify MFA Token](#verify-mfa)
- [Deactivate MFA](#deactivate-mfa)
- [Enable MFA](#enabledisable-mfa)
- [Disable MFA](#enabledisable-mfa)

### **Login**
The login shall require two steps for implementation of the overall authentication using the MFA approach. The first step shall generate the challenge, identified as a session token. The second step involves the OTP/TOTP code against that session token.

#### API based Approach
The first step API is common for the MFA enabled / disabled implementation.

```php
    
    ...

    public function actionLogin(Request $request)
    {
        //Create credentials object
        $collection = collect($request->all());

        if ($claim = $this->attemptLogin($collection, 'api', 'username', 'password', true)) {

            if ($claim instanceof AwsCognitoClaim) {
                return $claim->getData();
            } else {
                return $claim;
            } //End if
        }
    } //Function ends

```
If the MFA is disabled, the response shall have the access token, refresh token and the id token.
```json

```

In case the MFA is enabled and activated, then the response will be as shown below.
```json

{
    "status": "SOFTWARE_TOKEN_MFA",
    "session": "AYABeEkKMeJKkzhx3MK-GzS3ISIAH
    QABAAdTZXJ2aWNlABBDb2duaXRvVXNlclBvb2xzAA
    ...
    ...
    jVrz53Y1uJ3I30w46CpL9xlB50IbVJ0SNYY_tuFsLc
    GjYfDpn7XQcd6-fXWovCIYoMH5Q"
}

```

#### Web Application Approach
The first step for the web application is same for MFA enabled / disabled implementation.

```php

```

### **Activate MFA**

### **Verify MFA**

### **Deactivate MFA**

### **Enable/Disable MFA**