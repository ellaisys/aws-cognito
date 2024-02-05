<?php

namespace Ellaisys\Cognito\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Ellaisys\Cognito\Auth\AuthenticatesUsers; //Added for AWS Cognito

class LoginController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    public $redirectTo = 'home';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }


    /**
     * Authenticate User
     * 
     * @throws \HttpException
     * 
     * @return mixed
     */
    public function login(Request $request)
    {
        try {
            //Return object
            $returnValue = null;

            //Convert request to collection
            $collection = collect($request->all());

            //Authenticate with Cognito Package Trait (with 'web' as the auth guard)
            if ($response = $this->attemptLogin($collection, 'web')) {
                if ($response===true) {
                    $request->session()->regenerate();

                    $returnValue = redirect(route(config('cognito.redirect_to_route_name', $this->redirectTo)));
                } elseif ($response===false) {
                    $returnValue = redirect()
                        ->back()
                        ->withInput($request->only('username', 'remember'))
                        ->withErrors([
                            'username' => 'Incorrect username and/or password !!',
                        ]);

                    //$this->incrementLoginAttempts($request);
                    //
                    //$this->sendFailedLoginResponse($collection, null);
                } else {
                    $returnValue = $response;
                } //End if
            } //End if

            return $returnValue;
        } catch(Exception $e) {
            Log::error($e->getMessage());

            return $response->back()
                ->withInput($request)
                ->withErrors(['error' => $e->getMessage()]);
        } //Try-catch ends
    } //Function ends


    /**
     * Complete the MFA process proving the code sent to the user.
     * 
     * @param \Illuminate\Http\Request $request
     * 
     * @throws \HttpException
     */
    public function actionValidateMFACode(Request $request)
    {
        try
        {
            //Return object
            $returnValue = null;

            //Create credentials object
            $collection = collect($request->all());

            //Authenticate the user request
            $response = $this->attemptLoginMFA($request);
            if ($response===true) {
                $request->session()->regenerate();
                $returnValue = redirect(route(config('cognito.redirect_to_route_name', $this->redirectTo)));
            } elseif ($response===false) {
                $returnValue = redirect()
                    ->back()
                    ->withInput($request->only('username', 'remember'))
                    ->withErrors([
                        'username' => 'Incorrect username and/or password !!',
                    ]);
            } else {
                $returnValue = $response;
            } //End if

            return $returnValue;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $response = $this->sendFailedLoginResponse($collection, $e);

            return $response
                ->back()
                ->withInput($request->only('username', 'remember'))
                ->withErrors([
                    'error' => $e->getMessage(),
                ]);
        } //try-catch ends
    } //Function ends

} //Class ends
