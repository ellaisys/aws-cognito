<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoDeviceRememberedStatus;

use Ellaisys\Cognito\Events\Auth\PostPasskeyCompleteEvent;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait DeviceActions
{
    use BaseAuthTrait;

    /**
     * Gets the details of a device for the currently signed-in user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Validate payload
            $validator = Validator::make($request->all(), [
                'limit' => ['sometimes', 'integer'],
                'pagination_token' => ['sometimes', 'string']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            //Get the response from AWS Cognito for listing the devices
            $response = $client->listDevices(
                $accessToken,
                $request['limit'] ?? 10,
                $request['pagination_token'] ?? null
            );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('DeviceActions:list:Exception');
            throw $e;
        } //End try

        return $returnValue;
    } //Function ends

    /**
     * Action to create/confirm a device for the currently signed-in user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Validate payload
            $validator = Validator::make($request->all(), [
                'device_key' => ['required', 'string'],
                'device_name' => ['sometimes', 'string'],
                'device_config' => ['sometimes', 'string']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            // Get the device configuration from the request if present
            $deviceConfig = [];
            if ($request->has('device_config')) {
                $deviceConfig = json_decode($request['device_config'], true);
                if (!$deviceConfig) {
                    throw new HttpException(400, 'Invalid JSON in device_config');
                }
            } //End if

            //Get the response from AWS Cognito for confirming the device
            $response = $client->confirmDevice(
                $accessToken,
                $request['device_key'],
                $request['device_name'] ?? null,
                $deviceConfig
            );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('DeviceActions:create:Exception');
            throw $e;
        } //End try

        return $returnValue;
    } //Function ends

    /**
     * Action to update a device for the currently signed-in user.
     *
     * @param Request $request
     * @param string|null $deviceKey (optional)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ?string $deviceKey=null)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Merge device key from route parameter if present
            if (!empty($deviceKey)) {
                $request->merge(['device_key' => $deviceKey]);
            } //End if

            //Validate payload
            $validator = Validator::make($request->all(), [
                'device_key' => ['required', 'string'],
                'remembered_status' => ['sometimes', 'boolean']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            //Get the remembered status from the request
            $rememberedStatus = CognitoDeviceRememberedStatus::REMEMBERED;
            if ($request->has('remembered_status')) {
                $rememberedStatus = $request['remembered_status'] ?
                    CognitoDeviceRememberedStatus::REMEMBERED :
                    CognitoDeviceRememberedStatus::NOT_REMEMBERED;
            } //End if

            //Get the response from AWS Cognito for updating the device
            $response = $client->updateDeviceStatus(
                    $accessToken,
                    $request['device_key'],
                    $rememberedStatus
                );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('DeviceActions:update:Exception');
            throw $e;
        }

        return $returnValue;
    } //Function ends

    /**
     * Action to authenticate by responding to the passkey authentication challenge
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function challenge(Request $request, ?string $challengeName = null)
    {
        try {
            // Initialize variables
            $returnValue = null;

            if (!empty($challengeName)) {
                $request->merge(['challenge_name' => $challengeName]);
            } //End if

            // If username present in query parameters is email, decode it before validation and processing
            $email = $this->getDataFromQueryParam($request, 'username', EncryptionTypes::URL_ENCODE, true);
            if (!empty($email)) {
                $request->merge(['username' => $email]);
            } //End if

            //Convert challenge name to upper case if present in the request
            if ($request->has('challenge_name')) {
                $request->merge(['challenge_name' => strtoupper($request['challenge_name'])]);
            } //End if
        
            //Validate payload
            $validator = Validator::make($request->all(), [
                'username' => ['required'],
                'challenge_name' => ['sometimes', 'in:WEB_AUTHN,EMAIL_OTP,SMS_OTP']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get the response from AWS Cognito for authenticating with passkey credentials
            $response = $client->authWebAuthnCredential(
                CognitoAuthFlowTypes::USER_AUTH,
                $request['username'],
                $request['challenge_name'] ?? null
            );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('DeviceActions:challenge:Exception');
            throw $e;
        }

        return $returnValue;
    } //Function ends

    /**
     * Action to delete a device for the currently signed-in user.
     *
     * @param Request $request
     * @param string|null $deviceKey (optional)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, ?string $deviceKey=null)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Merge device key from route parameter if present
            if (!empty($deviceKey)) {
                $request->merge(['device_key' => $deviceKey]);
            } //End if

            //Validate payload
            $validator = Validator::make($request->all(), [
                'device_key' => ['required', 'string']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            //Get the response from AWS Cognito for deleting the passkey authenticator
            $response = $client->forgetDevice(
                $accessToken,
                $request['device_key']
            );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('DeviceActions:delete:Exception');
            throw $e;
        } //End try

        return $returnValue;
    } //Function ends

} //Trait ends
