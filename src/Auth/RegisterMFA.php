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
    public function activateMFA(string $guard='web'): mixed
    {
        return Auth::guard($guard)->associateSoftwareTokenMFA();
    } //Function ends

    /**
     * Verify the MFA for the authenticated user
     *
     * @param  string  $guard (optional)
     *
     * @return \mixed
     */
    public function verify(Request $request, 
        ?string $code=null, ?string $deviceName=null): mixed
    {
        try {
            // Initialize variables
            $returnValue = null;

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

            // Verify the MFA for the authenticated user
            $response = Auth::guard($guard)->verifySoftwareTokenMFA(
                $request['code'],
                $request['device_name'] ?? 'My Device'
            );

            // If the verification is successful, toggle ON the MFA for the authenticated user
            if (!empty($response) && ($response['Status']=='SUCCESS')) {
                $this->toggleMFA($request, true, true);
            } //End if

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect(back())
                    ->with('status', 'MFA verified successfully')
                    ->with('data', $response);
            } //Return response
        } catch (Exception $exception) {
            Log::error('RegisterMFA:verify:Exception');
            Log::error($exception);
            throw $exception;
        } //End try

        return $returnValue;
    } //Function ends

    /**
     * Deactivate the MFA for the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \mixed
     */
    public function deactivate(Request $request): mixed
    {
        return $this->toggleMFA($request, false);
    } //Function ends

    /**
     * Toggle the MFA for the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \bool  $isEnable (optional)
     *
     * @return \mixed
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
                $returnValue = redirect(back())
                    ->with('status', ($isEnable ? 'MFA activated successfully' : 'MFA deactivated successfully'))
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
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function enable(Request $request): mixed
    {
        return $this->toggleAdminMFA($request, true);
    } //Function ends

    /**
     * Disable the MFA for the mentioned user
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    public function disable(Request $request): mixed
    {
        return $this->toggleAdminMFA($request, false);
    } //Function ends

    /**
     * Toggle the MFA by the admin user
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool    $isEnable (optional)
     *
     * @return array
     */
    private function toggleAdminMFA(Request $request, bool $isEnable=false): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get Authenticated user
            $authUser = $this->getAuthenticatedUser($request);
           
            //Get the response from AWS Cognito for the MFA configurations
            $response = $client->setUserMFAPreferenceByAdmin($authUser->email, $isEnable);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect(back())
                    ->with('status', ($isEnable ? 'MFA enabled successfully' : 'MFA disabled successfully'))
                    ->with('data', $response);
            } //Return response
        } catch (Exception $exception) {
            Log::error('RegisterMFA:toggleAdminMFA:Exception');
            throw $exception;
        } //End try

        return $returnValue;
    } //Function ends

} //Trait ends
