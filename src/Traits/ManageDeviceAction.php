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
trait ManageDeviceAction
{
    /**
     * List all the devices.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_ListDevices.html
     *
     * @param string $accessToken
     * @param int $limit (default: 10)
     * @param string|null $paginationToken
     *
     * @return AwsResult
     */
    final public function listDevices(string $accessToken, int $limit=10,
        ?string $paginationToken=null): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'AccessToken' => $accessToken,
                'Limit' => $limit,
            ];

            if (!empty($paginationToken)) {
                $payload['PaginationToken'] = $paginationToken;
            } //End if

            //Execute the payload
            $response = $this->client->listDevices($payload);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('ManageDeviceAction:listDevices:CognitoIdentityProviderException');
            throw AwsCognitoException::create($e);
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
    final public function getDevice(string $accessToken, string $deviceKey): AwsResult
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
            Log::error('ManageDeviceAction:getDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($e);
        } //Try-catch ends

        return $response;
    } //Function ends

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
    final public function confirmDevice(string $accessToken, string $deviceKey,
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
            Log::error('ManageDeviceAction:confirmDevice:CognitoIdentityProviderException');
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
    final public function updateDeviceStatus(string $accessToken, string $deviceKey,
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
            Log::error('ManageDeviceAction:updateDeviceStatus:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
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
    final public function forgetDevice(string $accessToken, string $deviceKey): AwsResult
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
            Log::error('ManageDeviceAction:forgetDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * List all the devices for a user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminListDevices.html
     *
     * @param string $username
     * @param string|null $paginationToken
     * @param int $maxResults (default: 10)
     *
     * @return AwsResult
     */
    final public function adminListDevices(string $username, ?string $paginationToken=null, int $maxResults = 10): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'UserPoolId' => $this->poolId,
                'Limit' => $maxResults,
            ];

            if (!empty($paginationToken)) {
                $payload['PaginationToken'] = $paginationToken;
            } //End if

            //Execute the payload
            $response = $this->client->adminListDevices($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageDeviceAction:adminListDevices:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Get the device details for a user.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminGetDevice.html
     *
     * @param string $username
     * @param string $deviceKey
     *
     * @return AwsResult
     */
    final public function adminGetDevice(string $username, string $deviceKey): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'DeviceKey' => $deviceKey,
                'UserPoolId' => $this->poolId,
            ];

            //Execute the payload
            $response = $this->client->adminGetDevice($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageDeviceAction:adminGetDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Forget/Delete the device for a user.
     * https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminForgetDevice.html
     *
     * @param string $username
     * @param string $deviceKey
     *
     * @return AwsResult
     */
    final public function adminForgetDevice(string $username, string $deviceKey): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'DeviceKey' => $deviceKey,
                'UserPoolId' => $this->poolId,
            ];

            //Execute the payload
            $response = $this->client->adminForgetDevice($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageDeviceAction:adminForgetDevice:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

    /**
     * Updates the device status as an administrator.
     * @see https://docs.aws.amazon.com/cognito-user-identity-pools/latest/APIReference/API_AdminUpdateDeviceStatus.html
     *
     * @param string $username
     * @param string $deviceKey
     * @param CognitoDeviceRememberedStatus $rememberStatus (default: REMEMBERED)
     *
     * @return AwsResult
     */
    final public function adminUpdateDeviceStatus(string $username, string $deviceKey,
        CognitoDeviceRememberedStatus $rememberStatus=CognitoDeviceRememberedStatus::REMEMBERED): AwsResult
    {
        try {
            //Build payload
            $payload = [
                'Username' => $username,
                'DeviceKey' => $deviceKey,
                'UserPoolId' => $this->poolId,
                'DeviceRememberedStatus' => $rememberStatus->value,
            ];

            $response = $this->client->adminUpdateDeviceStatus($payload);
        } catch (CognitoIdentityProviderException $exception) {
            Log::error('ManageDeviceAction:adminUpdateDeviceStatus:CognitoIdentityProviderException');
            throw AwsCognitoException::create($exception);
        } //Try-catch ends

        return $response;
    } //Function ends

} //Trait ends
