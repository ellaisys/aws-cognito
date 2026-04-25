<?php

namespace Ellaisys\Cognito\Enums;

/**
 * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminGetUser.html#CognitoUserPools-AdminGetUser-response-UserStatus
 *
 * Refer UserStatus Parameters in the AdminGetUser request section
 */
enum CognitoUserStatusTypes: string
{
    case UNCONFIRMED = 'UNCONFIRMED';
    case CONFIRMED = 'CONFIRMED';
    case ARCHIVED = 'ARCHIVED';
    case COMPROMISED = 'COMPROMISED';
    case UNKNOWN = 'UNKNOWN';
    case RESET_REQUIRED = 'RESET_REQUIRED';
    case FORCE_CHANGE_PASSWORD = 'FORCE_CHANGE_PASSWORD';
    case EXTERNAL_PROVIDER = 'EXTERNAL_PROVIDER';
}
