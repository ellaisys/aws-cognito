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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;
use Ellaisys\Cognito\Auth\ConfirmsPasswords; //Added for AWS Cognito

use Exception;

class ConfirmPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Confirm Password Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password confirmations and
    | uses a simple trait to include the behavior. You're free to explore
    | this trait and override any functions that require customization.
    |
    */

    use ConfirmsPasswords;

    /**
     * Where to redirect users when the intended url fails.
     *
     * @var string
     */
    protected $redirectTo = 'cognito.form.login';

	/**
	 * Action to update the user password
	 *
	 * @param  \Illuminate\Http\Request  $request
	 */
    public function change(Request $request)
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

            //Validate request
            $validator = Validator::make($request->all(), [
                'email'    => 'sometimes|email',
                'password'  => 'required',
                'new_password' => 'required|confirmed',
            ]);
            $validator->validate();

            //Change password
            $response = $this->confirm($request, $guard);

            //Check the password
            if ($isJsonResponse) {
                $returnValue = $this->response->success($response);
            } else {
                //Logout on success
                auth()->guard()->logout(true);
                $request->session()->invalidate();

                $returnValue = redirect(route($this->redirectTo))
                    ->with('success', true)
                    ->with('message', 'Password successfully changed.');
			} //End if

            return $returnValue;
        } catch(Exception $e) {
            Log::error('ConfirmPasswordController:change:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

} //Class ends
