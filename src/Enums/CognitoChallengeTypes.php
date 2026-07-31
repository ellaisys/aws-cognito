<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Enums;

/**
 * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html#API_InitiateAuth_ResponseSyntax
 * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_RespondToAuthChallenge.html
 *
 * Refer AvailableChallenges and ChallengeName section
 */
enum CognitoChallengeTypes: string
{
   case PASSWORD = 'PASSWORD';
   case NEW_PASSWORD_REQUIRED = 'NEW_PASSWORD_REQUIRED';
   case RESET_REQUIRED = 'RESET_REQUIRED';

   case SELECT_CHALLENGE = 'SELECT_CHALLENGE';
   case CUSTOM_CHALLENGE = 'CUSTOM_CHALLENGE';

   // Admin challenges
   case ADMIN_NO_SRP_AUTH = 'ADMIN_NO_SRP_AUTH';

   //MFA challenges
   case MFA_SETUP = 'MFA_SETUP';
   case SELECT_MFA_TYPE = 'SELECT_MFA_TYPE';
   case EMAIL_MFA = 'EMAIL_MFA';
   case SMS_MFA = 'SMS_MFA';
   case SOFTWARE_TOKEN_MFA = 'SOFTWARE_TOKEN_MFA';

   // FIDO2 / Passkey authentication challenges
   case WEB_AUTHN = 'WEB_AUTHN';
   case EMAIL_OTP = 'EMAIL_OTP';
   case SMS_OTP = 'SMS_OTP';

   // SRP authentication challenges
   case PASSWORD_SRP = 'PASSWORD_SRP';
   case PASSWORD_VERIFIER = 'PASSWORD_VERIFIER';

   // Device authentication challenges
   case DEVICE_SRP_AUTH = 'DEVICE_SRP_AUTH';
   case DEVICE_PASSWORD_VERIFIER = 'DEVICE_PASSWORD_VERIFIER';
}
