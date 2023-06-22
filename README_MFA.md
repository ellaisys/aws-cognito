## **MFA Functionality**
The library currently provides only the MFA for the Software Token. The SMS based TOPT is still under development and shall be released shortly.

## **Configurations**
The package provides a trait that you can add to your controller to make the MFA methods running.

- Ellaisys\Cognito\Auth\RegisterMFA

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
The activate process allows the user to configure the MFA. In case of a Software Token MFA setting on the mobile device, a key or the scan code will make it easy to consume the MFA using any of the authenticator applications (i.e. Google Authentictor OR Microsoft Authenticator).

The process completes when the code is verified using the [Verified MFA](#verify-mfa) step.

#### Web and API based Approach
The function call looks as shown below. Just reference the the method activateMFA, with the guard name as a parameter, in the trait that you added above in configuration. This shall activate the Software MFA token.

```php

    public function actionActivate()
    {
		try
		{
            return $this->activateMFA('api'); //Pass the guard name for web/api calls
        } catch(Exception $e) {
			throw $e;
        } //Try-catch ends
    } //Function ends

```
The response that you will get for the API call would look this

```json
    {
        "SecretCode": "ESKPE46WBNOAB7QXXXXXXXXXXXXXXXXXXXPFIVJVJFEPDP2NNIA",
        "SecretCodeQR": "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=otpauth://totp/ApplicationName (john@doe.com)?secret=ESKPE46WBNOAB7QXXXXXXXXXXXXXXXXXXXPFIVJVJFEPDP2NNIA&issuer=ApplicationName&choe=UTF-8",
        "TotpUri": "otpauth://totp/ApplicationName (john@doe.com)?secret=ESKPE46WBNOAB7QXXXXXXXXXXXXXXXXXXXPFIVJVJFEPDP2NNIA&issuer=ApplicationName"
    }
```

and the web response, you can design a page like this to show the code for activating the Software MFA token.

<img src="./assets/images/web_application_activate.png" width="50%" alt="cognito mfa activate for web"/>

### **Verify MFA**
In order to complete the activation process, the verification is an essential step. As part of this verification process, you need to enter the code (available in the authenticator application) while submitting the request. Depending upon the web or api controller, the impementation needs to be updated. The response will be HTTP Status Code 200.

```php

    public function actionVerify(string $code)
    {
		try
		{
            return $this->verifyMFA('api', $code); //Pass the guard name for web/api calls and the MFA code from device
        } catch(Exception $e) {
			throw $e;
        } //Try-catch ends
    } //Function ends

```


### **Deactivate MFA**

### **Enable/Disable MFA**