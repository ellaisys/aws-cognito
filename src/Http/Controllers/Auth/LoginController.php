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

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;

use Ellaisys\Cognito\Events\Auth\PreAuthEvent;
use Ellaisys\Cognito\Events\Auth\PostAuthSuccessEvent;
use Ellaisys\Cognito\Events\Auth\PostAuthFailedEvent;
use Ellaisys\Cognito\Events\Auth\PreLogoutEvent;
use Ellaisys\Cognito\Events\Auth\PostLogoutEvent;

use Exception;

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
        $this->middleware('guest')->except(['logout', 'logoutForced']);

        parent::__construct();
    }


    /**
     * Authenticate User
     * @param \Illuminate\Http\Request $request
     * @param string $usernameField
     * @param string $passwordField
     *
     * @throws \HttpException
     *
     * @return mixed
     */
    public function login(Request $request,
        string $usernameField='username', string $passwordField='password')
    {
        try {
            //Raise Pre Auth Event
            event(new PreAuthEvent(
                $request->except($passwordField),
                $request->ip()
            ));

            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Convert request to collection
            $collection = collect($request->all());

            //Check if request is json
            if ($request->expectsJson() || $request->isJson()) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Authenticate with Cognito Package Trait based on the guard
            if ($claim = $this->attemptLogin($collection, $guard,
                $usernameField, $passwordField, $isJsonResponse, true)) {
                
                if ($claim instanceof AwsCognitoClaim) { // Success authentication
                    //Raise Post Auth Success Event
                    $user = Auth::guard($guard)->user();
                    event(new PostAuthSuccessEvent(
                        $user->toArray(),
                        $request->except('password'),
                        $request->ip()
                    ));

                    if ($isJsonResponse) {
                        $returnValue = $this->response->success($claim->getData());
                    } else {
                        $request->session()->regenerate();
                        $returnValue = redirect(route(config('cognito.redirect_to_route_name', $this->redirectTo)));
                    } //End if
                } else { // Challenge generated
                    //Raise Post Auth Success Event
                    event(new PostAuthSuccessEvent(
                        [],
                        $request->except('password'),
                        $request->ip()
                    ));

                    $returnValue = $this->response->success([]);
                } //End if

                    // if ($response===true) {
                    //     $request->session()->regenerate();

                    //     $returnValue = redirect(route(config('cognito.redirect_to_route_name', $this->redirectTo)));
                    // } elseif ($response===false) {
                    //     $returnValue = redirect()
                    //         ->back()
                    //         ->withInput($request->only($usernameField, 'remember'))
                    //         ->withErrors([
                    //             $usernameField => 'Incorrect username and/or password !!',
                    //         ]);
                    // } else {
                    //     $returnValue = $response;
                    // } //End if
            } //End if

            return $returnValue;
        } catch(Exception $e) {
            Log::error('AuthController:actionLogin:Exception');

            //Rise Post Auth Failed Event
            event(new PostAuthFailedEvent(
                $request->except('password'),
                $e, $request->ip()
            ));

            if ($isJsonResponse) {
                throw $e;
            } //End if

            return response()->back()
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


    /**
     * Logout action for the API based approach.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function logout(Request $request, bool $forced = false)
    {
        try {
            //Raise Pre Logout Event
            event(new PreLogoutEvent(
                $request->toArray(),
                $request->ip()
            ));

            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($request->expectsJson() || $request->isJson()) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Logout user
            Auth::guard($guard)->logout($forced);

            //Raise Post Logout Event
            event(new PostLogoutEvent(
                $request->toArray(),
                $request->ip()
            ));

            //Send response data
            if ($isJsonResponse) {
                $returnValue = $this->response->success([]);
            } else {
                $request->session()->invalidate();
                $returnValue = redirect('/');
            } //End if
        } catch (Exception $e) {
            throw new HttpException(400, 'Error logging out.');
        } //End try-catch
        return $returnValue;
    } //Function ends
    public function logoutForced(Request $request)
    {
        return $this->logout($request, true);
    } //Function ends

} //Class ends
