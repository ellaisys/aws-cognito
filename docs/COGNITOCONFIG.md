# **AWS Configurations**

This document provides guidance on configuring AWS services, specifically AWS Cognito. Ensure you have the necessary permissions and knowledge to perform these configurations.

> [!NOTE]
> Updated On 2026-07-10


## **AWS IAM configuration**

You will need a new `IAM Role` with the following Access Rights:

- AmazonCognitoDeveloperAuthenticatedIdentities
- AmazonCognitoPowerUser
- AmazonESCognitoAccess

From this IAM User you must use the `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` in the laravel environment file.


## **AWS Cognito configuration**

This document is shared for those requiring assistance or clarification in configuring the **AWS Cognito**. Make sure you have a valid **AWS account** and have successfully authenticated into it.

This document is intended to provide guidance on configuring AWS Cognito for your application. It is not a comprehensive guide to all AWS Cognito features, but rather focuses on the essential steps needed to set up a user pool and app client for your application.

Any additional configurations or customizations that are required for your specific application should be done based on your application's requirements and AWS Cognito documentation.

In case of MFA (Multi-Factor Authentication), SRP, Device Authentication requirements, please refer to the configuration settings in respective sections of the AWS Cognito documentation.

> [!TIP]
> Focus on the sections highlighted in **RED** and **YELLOW** boxes. These are for modification and attention, respectively. The other sections are for your information only.

### *Step 1: AWS Cognito Service*
---

![AWS Cognito Service](../assets/images/aws_cognito_flow1.png)


### *Step 2: Create the New User Pool*
---

![AWS Cognito - Create New User Pool](../assets/images/aws_cognito_flow2.png)

During the creation of the new user pool, you will be asked to provide a **Application Name** (Client Name). Please provide a name that is relevant to your application. AWS will automatically generate a **Pool Name** and you can also modify the **Pool Name** while amending the pool settings.

While creating the new user pool, you will be asked to select the **Configure Options**. Please select the options that are relevant to your application.

Enable **Self Registration** if you want your users to be able to register themselves. If you do not enable this option, then the users will have to be created by an administrator by inviting them to the application. You can also enable **Email Verification** if you want your users to verify their email addresses.


### *Step 3: New User Pool Created*
---

![AWS Cognito - New User Pool Created](../assets/images/aws_cognito_flow3.png)

Once the new user pool is created, navigate by selecting the **User pools** from the left menu and select the newly created user pool.


### *Step 4: User Pool Overview*
---

![AWS Cognito - User Pool Overview](../assets/images/aws_cognito_flow4.png)

By selecting the **Overview** from the left menu, you will be able to see the **Pool Id** and **Pool ARN**. Please save these parameters.

The **Pool Id** will be used in the laravel environment file as `AWS_COGNITO_POOL_ID` value.

Select the **App clients** from the left menu, under the **Application** section. It will show a list of the app clients. Select the app client that you created in the previous step.


### *Step 5: App Client Overview*
---

![AWS Cognito - App Client](../assets/images/aws_cognito_flow5.png)

This page will show the **Client Id** and **Client Secret**.
Please save these parameters. You will need to set them in the laravel environment file to reference `AWS_COGNITO_CLIENT_ID` and `AWS_COGNITO_CLIENT_SECRET` values.

You can copy the values by clicking the blue copy icon next to the values (highlighted by the red arrow).


### *Step 6: Edit App Client Settings*
---

![AWS Cognito - Edit App Client Settings](../assets/images/aws_cognito_flow6.png)

You can amend the **App Client Name** to a more relevant name if you wish.

In the **Authentication Flows** section, please select the following:
- `ALLOW_ADMIN_USER_PASSWORD_AUTH` (Essential for admin endpoints)
- `ALLOW_USER_PASSWORD_AUTH` (User authentication with username and password)
- `ALLOW_REFRESH_TOKEN_AUTH` (Required for refresh token)

Optionally, you can select the following, based on your application requirements:
- `ALLOW_USER_SRP_AUTH` (User authentication with SRP)
- `ALLOW_USER_AUTH` (Passwordless authentication)

Adjust the attributes below based on your application requirements, or leave them as default.

Save the changes by clicking the **Save Changes** button at the bottom of the page.


### *Step 7: Review and Amend Login Page Settings*
---

![AWS Cognito - Review and Amend Login Page Settings](../assets/images/aws_cognito_flow7.png)

In the **App client** overview page, select the **Login page** from the tab below the overview section, as shown in the image above. Click the **Edit** button to amend the settings.


### *Step 8: Login Page Settings*
---

![AWS Cognito - Login Page Settings](../assets/images/aws_cognito_flow8.png)

You can update the values as shown in the image above. Please ensure that you have selected the correct **Callback URL(s)** and **Sign out URL(s)**. These URLs are important for your application to redirect users after login and logout.

> [!NOTE]
> In development environments, you can use `http://localhost` as the **Callback URL** and `http://localhost` as the **Sign out URL**.

Select the **Identity Providers** dropdown and ensure that the **Cognito User Pool** is selected. You can also select other identity providers if you have configured them.

Select the **OAuth 2.0 Grant Types** dropdown and ensure that the **Authorization code grant** is selected.

In the **OpenID Connect** (OIDC) scopes section, ensure that the following scopes are selected:
- `email`
- `openid`

Save the changes by clicking the **Save Changes** button at the bottom of the page.
