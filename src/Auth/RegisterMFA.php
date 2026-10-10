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

use Aws\Result as AwsResult;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait RegisterMFA
{
    use BaseAuthTrait;

    /**
     * Activate the MFA for the authenticated user
     *
     * @param  string  $guard (optional)
     *
     * @return mixed
     */
    final public function activate(Request $request): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            // Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            // Token Object
            $accessToken = $this->getAccessToken($request);

            // Get authenticated user
            $user = $this->getAuthenticatedUser($request);
            $username = $user['username'] ?? $user['email'] ?? '';

            // Get the response from AWS Cognito for the MFA configurations
            $response = $client->associateSoftwareTokenMFA($accessToken);

            // Build payload
            $secretCode = $response->get('SecretCode');
            $uriTotp = '';
            $uriTotp .= 'otpauth://totp/' . config('app.name');
            $uriTotp .= ' (' . $username . ')?secret=' . $secretCode;
            $uriTotp .= '&issuer=' . config('app.name');

            $returnValue = [
                'SecretCode' => $secretCode,
                'SecretCodeQR' => config('cognito.mfa_qr_library') . $uriTotp,
                'TotpUri' => $uriTotp
            ];

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $returnValue;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($returnValue);
            } else {
                $returnValue = view('cognito::partials.mfa.activate-form', [
                    'status' => 'success',
                    'message' => __('cognito::messages.mfa.activation_success'),
                    'data' => $returnValue
                ]);
            } //Return response
        } catch (Exception $exception) {
            Log::error('RegisterMFA:activate:Exception');
            throw $exception;
        } //End try
        return $returnValue;
    } //Function ends

    /**
     * Verify the MFA for the authenticated user
     *
     * @param \Illuminate\Http\Request $request
     * @param string $code (optional)
     * @param string $deviceName (optional)
     *
     * @return mixed
     */
    final public function verify(Request $request,
        ?string $code=null, ?string $deviceName=null): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            // Merge the request data with the provided code and device name
            if (!empty($code)) {
                $request->merge(['code' => $code]);
            } //End if
            if (!empty($deviceName)) {
                $request->merge(['device_name' => $deviceName]);
            } //End if

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'code' => 'required|string',
                'device_name' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            // Get the guard
            $guard = $this->getGuard($request);

            // Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            // Token Object
            $accessToken = $this->getAccessToken($request);

            // Verify the MFA for the authenticated user
            $response = $client->verifySoftwareTokenMFA(
                $request['code'], $accessToken,
                null, $request['device_name'] ?? 'My Device');

            // Toggle ON the MFA for the authenticated user
            $this->toggleMFA($request, true, true);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->back()
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.mfa.verification_success'))
                    ->with('data', $response);
            } //Return response
        } catch (Exception $exception) {
            Log::error('RegisterMFA:verify:Exception');
            throw $exception;
        } //End try

        return $returnValue;
    } //Function ends

    /**
     * Deactivate the MFA for the authenticated user
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    final public function deactivate(Request $request): mixed
    {
        return $this->toggleMFA($request, false);
    } //Function ends

    /**
     * Toggle the MFA for the authenticated user
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $isEnable (optional)
     * @param bool $isDirectCall (optional)
     *
     * @return mixed
     * @throws \Exception
     */
    private function toggleMFA(Request $request, bool $isEnable=false, bool $isDirectCall=false): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            // Set the MFA preference for the authenticated user
            $response = $client->setUserMFAPreference($accessToken, $isEnable);
            
            //Return response
            if ($this->isControllerAction || $isDirectCall) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $messageKey = 'cognito::messages.mfa.';
                $messageKey .= $isEnable ? 'activation_success' : 'deactivation_success';

                $returnValue = redirect(back())
                    ->with('status', 'success')
                    ->with('message', trans($messageKey))
                    ->with('data', $response);
            } //Return response
        } catch (Exception $exception) {
            Log::error('RegisterMFA:toggleMFA:Exception');
            throw $exception;
        } //End try

        return $returnValue;
    } //Function ends

    /**
     * Enable the MFA for the mentioned user
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    final public function enable(Request $request): mixed
    {
        return $this->toggleAdminMFA($request, true);
    } //Function ends

    /**
     * Disable the MFA for the mentioned user
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    final public function disable(Request $request): mixed
    {
        return $this->toggleAdminMFA($request, false);
    } //Function ends

    /**
     * Change the MFA settings for the mentioned user by the admin
     *
     * @param \Illuminate\Http\Request $request
     * @param bool $isEnable (optional)
     *
     * @return mixed
     * @throws \Exception
     */
    private function toggleAdminMFA(Request $request, bool $isEnable=false): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            if (!$request->has('username')) {
                //Get Authenticated user
                $authUser = $this->getAuthenticatedUser($request);

                // Merge the request data with the authenticated user's username
                $request->merge(['username' => $authUser['email']]);
            } //End if

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'username' => 'required|string'
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);
           
            //Get the response from AWS Cognito for the MFA configurations
            $response = $client->adminSetUserMFAPreference($request['username'], $isEnable);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $messageKey = 'cognito::messages.mfa.';
                $messageKey .= $isEnable ? 'enabled_success' : 'disabled_success';

                $returnValue = redirect()
                    ->back()
                    ->with('status', 'success')
                    ->with('message', trans($messageKey))
                    ->with('data', $response);
            } //Return response
        } catch (Exception $exception) {
            Log::error('RegisterMFA:toggleAdminMFA:Exception');
            throw $exception;
        } //End try

        return $returnValue;
    } //Function ends

} //Trait ends
