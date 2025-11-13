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
    public function enable()
    {
		try
		{
            $user = auth()->guard('web')->user();
            $response = $this->enableMFA('web', $user->email);
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionEnableMFA', [
                    'message' => $response
                ]);
        } catch(Exception $e) {
			$message = 'Error activating the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			throw $e;
        } //Try-catch ends
    } //Function ends

	/**
	 * Action to disable MFA for the user
	 */
    public function disable()
    {
		try
		{
            $user = auth()->guard('web')->user();
            $response = $this->disableMFA('web', $user->email);
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionDisableMFA', [
                    'status' => $response['@metadata']['statusCode']==200
                ]);
        } catch(Exception $e) {
			$message = 'Error activating the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

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
            $response = $this->verifyMFA($guard, $code, $deviceName);

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
            Log::error('MFAController:deactivate:Exception');
			throw $e;
        } //Try-catch ends
    } //Function ends

} //Class ends
