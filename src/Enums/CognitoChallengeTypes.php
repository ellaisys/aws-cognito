<?php

namespace Ellaisys\Cognito\Enums;

/**
 * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html#API_InitiateAuth_ResponseSyntax
 *
 * Refer AvailableChallenges and ChallengeName section
 */
enum CognitoChallengeTypes: string
{
   case PASSWORD = 'PASSWORD';
   case PASSWORD_SRP = 'PASSWORD_SRP';
   case PASSWORD_VERIFIER = 'PASSWORD_VERIFIER';
   case NEW_PASSWORD_CHALLENGE = 'NEW_PASSWORD_REQUIRED';
   case DEVICE_PASSWORD_VERIFIER = 'DEVICE_PASSWORD_VERIFIER';

   case MFA_SETUP = 'MFA_SETUP';
   case SELECT_MFA_TYPE = 'SELECT_MFA_TYPE';
   case SMS_MFA = 'SMS_MFA';
   case SMS_OTP = 'SMS_OTP';
   case EMAIL_OTP = 'EMAIL_OTP';
   case SOFTWARE_TOKEN_MFA = 'SOFTWARE_TOKEN_MFA';

   case SELECT_CHALLENGE = 'SELECT_CHALLENGE';
   case CUSTOM_CHALLENGE = 'CUSTOM_CHALLENGE';

   case WEB_AUTHN = 'WEB_AUTHN';
   case DEVICE_SRP_AUTH = 'DEVICE_SRP_AUTH';
   case ADMIN_NO_SRP_AUTH = 'ADMIN_NO_SRP_AUTH';
}
