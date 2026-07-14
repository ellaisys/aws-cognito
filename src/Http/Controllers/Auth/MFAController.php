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

        // Controller action flag
        $this->setIsControllerAction(false);

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

} //Class ends
