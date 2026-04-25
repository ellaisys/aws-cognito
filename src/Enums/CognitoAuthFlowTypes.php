<?php

namespace Ellaisys\Cognito\Enums;

/**
 * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_InitiateAuth.html#API_InitiateAuth_ResponseSyntax
 *
 * Refer AuthFlow Parameters in the InitiateAuth request section
 */
enum CognitoAuthFlowTypes: string
{
    case USER_SRP_AUTH = 'USER_SRP_AUTH';
    case REFRESH_TOKEN_AUTH = 'REFRESH_TOKEN_AUTH';
    case REFRESH_TOKEN = 'REFRESH_TOKEN';
    case USER_PASSWORD_AUTH = 'USER_PASSWORD_AUTH';
    case USER_AUTH = 'USER_AUTH';
    case CUSTOM_AUTH = 'CUSTOM_AUTH';

    case ADMIN_NO_SRP_AUTH = 'ADMIN_NO_SRP_AUTH'; # Only for AdminInitiateAuth
    case ADMIN_USER_PASSWORD_AUTH = 'ADMIN_USER_PASSWORD_AUTH'; # Only for AdminInitiateAuth
}
