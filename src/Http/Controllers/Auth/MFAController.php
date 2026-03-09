<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Controllers\Auth;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Auth\RegisterMFA;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class MFAController extends Controller
{
    use RegisterMFA;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('aws-cognito');

        parent::__construct();
    }

    /**
     * Action to activate MFA
     */
    public function activate(Request $request)
    {
        try
        {
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Activate MFA
            $response = $this->activateMFA($guard);
            
            //Return status to screen
            if ($isJsonResponse) {
                $returnValue = $this->response->success($response, 200, 'MFA activated successfully');
            } else {
                $user = auth()->guard($guard)->user();
                $userCognito = auth()->guard($guard)->getRemoteUserData($user->email);

                $returnValue = back()
                    ->with('user', $userCognito->toArray())
                    ->with('status', 'MFA activated successfully')
                    ->with('actionActivateMFA', $response);
            } //End if
            return $returnValue;
        } catch(Exception $e) {
            Log::error('MFAController:activate:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Action to deactivate MFA
     */
    public function deactivate(Request $request)
    {
        try
        {
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Deactivate the MFA
            $response = $this->deactivateMFA($guard);

            if ((isset($response['@metadata']['statusCode'])) &&
                ($response['@metadata']['statusCode']==200)) {
                //Return status to screen
                if ($isJsonResponse) {
                    $returnValue = $this->response->success([], 200, 'MFA deactivated successfully');
                } else {
                    $user = auth()->guard($guard)->user();
                    $userCognito = auth()->guard($guard)->getRemoteUserData($user->email);

                    $returnValue = back()
                    ->with('user', $userCognito->toArray())
                    ->with('status', 'MFA deactivated successfully')
                    ->with('actionDeactivateMFA', $response);
                }
                return $returnValue;
            } else {
                throw new HttpException(400, 'Error deactivating the MFA.');
            } //End if
        } catch(Exception $e) {
            Log::error('MFAController:deactivate:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Action to enable MFA for the user
     */
    public function enable(Request $request)
    {
        try
        {
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            $user = auth()->guard($guard)->user();
            if (empty($user)) {
                throw new HttpException(400, 'User not found.');
            } //End if
            $response = $this->enableMFA($guard, $user->email);

            if ((isset($response['@metadata']['statusCode'])) &&
                ($response['@metadata']['statusCode']==200)) {
                //Return status to screen
                if ($isJsonResponse) {
                    $returnValue = $this->response->success([], 200, 'MFA enabled successfully');
                } else {
                    $userCognito = auth()->guard($guard)->getRemoteUserData($user->email);

                    $returnValue = back()
                        ->with('user', $userCognito->toArray())
                        ->with('status', 'MFA enabled successfully')
                        ->with('actionEnableMFA', [
                            'message' => $response
                        ]);
                }
                return $returnValue;
            } else {
                throw new HttpException(400, 'Error enabling the MFA.');
            } //End if
        } catch(Exception $e) {
            Log::error('MFAController:enable:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Action to disable MFA for the user
     */
    public function disable(Request $request)
    {
        try
        {
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            $user = auth()->guard($guard)->user();
            if (empty($user)) {
                throw new HttpException(400, 'User not found.');
            } //End if
            $response = $this->disableMFA($guard, $user->email);

            if ((isset($response['@metadata']['statusCode'])) &&
                ($response['@metadata']['statusCode']==200)) {
                //Return status to screen
                if ($isJsonResponse) {
                    $returnValue = $this->response->success([], 200, 'MFA disabled successfully');
                } else {
                    $userCognito = auth()->guard($guard)->getRemoteUserData($user->email);

                    $returnValue = back()
                        ->with('user', $userCognito->toArray())
                        ->with('status', 'MFA disabled successfully')
                        ->with('actionDisableMFA', [
                            'message' => $response
                        ]);
                }
                return $returnValue;
            } else {
                throw new HttpException(400, 'Error disabling the MFA.');
            } //End if
        } catch(Exception $e) {
            Log::error('MFAController:disable:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Verify the MFA user code
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function verify(Request $request, string $code=null, string $deviceName=null)
    {
        try
        {
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;
            $code = $code?:$request['code'];
            $deviceName = $deviceName?:$request['device_name'];

            //Verify MFA Code
            $this->verifyMFA($guard, $code, $deviceName);

            if ($isJsonResponse) {
                $returnValue = $this->response->success([], 200, 'MFA verified successfully');
            } else {
                $user = auth()->guard($guard)->user();
                $userCognito = auth()->guard($guard)->getRemoteUserData($user->email);

                //Return status to screen
                $returnValue = back()
                    ->with('user', $userCognito->toArray())
                    ->with('actionVerifyMFA', [
                        'status' => true
                    ]);
            }
            return $returnValue;
        } catch(Exception $e) {
            Log::error('MFAController:verify:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

} //Class ends
