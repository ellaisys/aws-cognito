<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Controllers\Api;

use Auth;
use Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Auth\ChangePasswords;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    use AuthenticatesUsers;
    use ChangePasswords;

    
    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Login action for the API based approach.
     * 
     * @param  \Illuminate\Http\Request  $request
     * 
     * @return \Illuminate\Http\Response
     */
    public function actionLogin(Request $request)
    {
        try {
            //Create credentials object
            $collection = collect($request->all());

            //Validate request and get credentials
            $claim = $this->attemptLogin($collection, 'api', 'username', 'password', true);
            if ($claim instanceof AwsCognitoClaim) {
                return $this->response->success($claim->getData());
            } else {
                return $this->response->success($claim);
            } //End if
        } catch (Exception $e) {
            return $e;
        } //End try-catch
        
    } //Function ends


    /**
     * Logout action for the API based approach.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function actionLogout(Request $request, bool $forced = false)
    {
        try {
            auth()->guard('api')->logout($forced);

            //Send response data
            return $this->response->success([], 200, 'Successfully logged out');
        } catch (Exception $e) {
            throw new HttpException(400, 'Error logging out.');
        } //End try-catch
    } //Function ends
    public function actionLogoutForced(Request $request)
    {
        return $this->actionLogout($request, true);
    } //Function ends


	/**
	 * Action to update the user password
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 */
    public function actionChangePassword(Request $request)
    {
		try
		{
            //Validate request
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password'  => 'required',
                'new_password' => 'required|confirmed',
            ]);
            $validator->validate();

            // Get Current User
            $userCurrent = auth()->guard('web')->user();

            if ($this->reset($request)) {
                return redirect(route('login'))->with('success', true);
            } else {
				return redirect()->back()
					->with('status', 'error')
					->with('message', 'Password updated failed');
			} //End if
        } catch(Exception $e) {
			$message = 'Error sending the reset mail.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			return redirect()->back()
				->with('status', 'error')
				->with('message', $message);
        } //Try-catch ends
    } //Function ends

} //Class ends
