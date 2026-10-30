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
 * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateDeviceStatus.html#CognitoUserPools-AdminUpdateDeviceStatus-request-DeviceRememberedStatus
 *
 * Refer DeviceRememberedStatus Parameters
 */
enum CognitoDeviceRememberedStatus: string
{
    case NOT_REMEMBERED = 'not_remembered';
    case REMEMBERED = 'remembered';
}
