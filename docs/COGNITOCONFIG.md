# AWS Configurations

## AWS IAM configuration

Also the details of the **AWS IAM configuration** is not included here, and we expect that you know it.

## AWS Cognito configuration

This document is shared for those requiring assistance or clarification in configuring the **AWS Cognito**. Make sure you have a valid **AWS account** and have successfully authenticated into it.

### Step 1: AWS Cognito Service
![AWS Cognito Service](../assets/images/aws_cognito_flow1.png)

### Step 2: Create the New User Pool
![AWS Cognito - Create New User Pool](../assets/images/aws_cognito_flow2.png)

During the creation of the new user pool, you will be asked to provide a **Pool Name**. Please provide a name that is relevant to your application.

While creating the new user pool, you will be asked to select the **Configure Options**. Please select the options that are relevant to your application.

Enable **Self Registration** if you want your users to be able to register themselves. If you do not enable this option, then the users will have to be created by an administrator by inviting them to the application. You can also enable **Email Verification** if you want your users to verify their email addresses.

### Step 3: Create the new pool
<img  src="../assets/images/aws_cognito_flow3.png"  width="100%"  alt="AWS Cognito Step3"  />

It is essential that the **ALLOW_ADMIN_USER_PASSWORD_AUTH** is selected.
------------
## Step 4: Create the App Client
<img  src="../assets/images/aws_cognito_flow4.png"  width="100%"  alt="AWS Cognito Step4"  />

Review the configurations as shown in the highlighted areas (Red Boxes) in the image above. Please also note the selections as shown for the OAuth Flows and Scopes.   
------------
## Step 5: Client Id and Client Secret
<img  src="../assets/images/aws_cognito_flow5.png"  width="100%"  alt="AWS Cognito Step5"  />

Please save these parameters. You will need to set them in the laravel environment file with the right keys. The key format is provided in the README file.  
------------
## Step 6: Pool Id
<img  src="../assets/images/aws_cognito_flow6.png"  width="100%"  alt="AWS Cognito Step6"  />
