<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Traits;

use Config;
use Carbon\Carbon;

use Aws\Result as AwsResult;

use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Ellaisys\Cognito\Enums\CognitoDeviceRememberedStatus;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * AWS Cognito Client for Device Actions
 */
trait AwsCognitoClientDeviceAction
{
    /**
     * Confirms/Create the device request.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ConfirmDevice.html
     *
     * @param string $accessToken
     * @param string $deviceKey
     * @param string|null $deviceName
     * @param array $payloadData
     *
     * @return AwsResult
     */
    public function confirmDevice(string $accessToken, string $deviceKey,
        ?string $deviceName=null, array $payloadData=[]): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'DeviceKey' => $deviceKey,
            ];

            if (!empty($deviceName)) {
                $payload['DeviceName'] = $deviceName;
            } //End if

            $payload = array_merge($payload, $payloadData);

            $response = $this->client->confirmDevice($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientDeviceAction:confirmDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Updates the device status.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_UpdateDeviceStatus.html
     *
     * @param string $accessToken
     * @param string $deviceKey
     * @param CognitoDeviceRememberedStatus $rememberStatus (default: REMEMBERED)
     *
     * @return AwsResult
     */
    public function updateDeviceStatus(string $accessToken, string $deviceKey,
        CognitoDeviceRememberedStatus $rememberStatus=CognitoDeviceRememberedStatus::REMEMBERED): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'DeviceKey' => $deviceKey,
                'DeviceRememberedStatus' => $rememberStatus->value,
            ];

            $response = $this->client->updateDeviceStatus($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientDeviceAction:updateDeviceStatus:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Get the device details.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_GetDevice.html
     *
     * @param string $accessToken
     * @param string $deviceKey
     *
     * @return AwsResult
     */
    public function getDevice(string $accessToken, string $deviceKey): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'DeviceKey' => $deviceKey,
            ];

            //Execute the payload
            $response = $this->client->getDevice($payload);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AwsCognitoClientDeviceAction:getDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($e);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Forget/Delete the device.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ForgetDevice.html
     *
     * @param string $accessToken
     * @param string $deviceKey
     *
     * @return AwsResult
     */
    public function forgetDevice(string $accessToken, string $deviceKey): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'DeviceKey' => $deviceKey,
            ];

            //Execute the payload
            $response = $this->client->forgetDevice($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('AwsCognitoClientDeviceAction:forgetDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
