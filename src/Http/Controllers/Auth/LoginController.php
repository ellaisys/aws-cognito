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
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

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
            //Initialize parameters
            $guard = 'web';
            $isJsonResponse = false;

            //Raise Pre Auth Event
            $this->callPreAuthEvent($request, $passwordField);

            //Convert request to collection
            $collection = collect($request->all());

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Authenticate with Cognito Package Trait based on the guard
            $claim = $this->attemptLogin($collection, $guard,
                $usernameField, $passwordField, $isJsonResponse, true);

            //Process the claim response
            return $this->processClaimResponse(
                    $request, $claim, $guard, $isJsonResponse,
                    $usernameField, $passwordField
                );
        } catch(Exception $e) {
            Log::error('AuthController:actionLogin:Exception');

            //Rise Post Auth Failed Event
            $this->callPostAuthErrorEvent($request, $e, $passwordField);

            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Complete the MFA process proving the code sent to the user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \HttpException
     */
    public function validateMFA(Request $request)
    {
        try
        {
            //Initialize parameters
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Authenticate the user request
            $claim = $this->attemptLoginMFA($request, $guard, true);

            //Process the claim response
            return $this->processClaimResponse(
                    $request, $claim, $guard, $isJsonResponse,
                    $usernameField, $passwordField
                );
        } catch (Exception $e) {
            Log::error('AuthController:validateMFA:Exception');

            //Rise Post Auth Failed Event
            $this->callPostAuthErrorEvent($request, $e, $passwordField);

            if ($isJsonResponse) {
                throw $e;
            } //End if

            return response()->back()
                ->withInput($request)
                ->withErrors(['error' => $e->getMessage()]);
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
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Raise Pre Logout Event
            event(new PreLogoutEvent(
                $request->toArray(),
                $request->ip()
            ));

            //Check if request is json
            if ($this->isJson($request)) {
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
                $returnValue = redirect(route('cognito.form.login'));
            } //End if
        } catch (Exception $e) {
            Log::error('LoginController:logout:Exception');
            throw $e;
        } //End try-catch
        return $returnValue;
    } //Function ends
    public function logoutForced(Request $request)
    {
        return $this->logout($request, true);
    } //Function ends

    private function processClaimResponse(Request $request, $claim,
        string $guard, bool $isJsonResponse,
        string $usernameField, string $passwordField): mixed
    {
        try
        {
            //Initialize parameters
            $returnValue = null;

            //Authenticate with Cognito Package Trait based on the guard
            if (!empty($claim)) {
                if ($isJsonResponse) {
                    if ($claim instanceof AwsCognitoClaim) { // Success authentication
                        //Raise Post Auth Success Event
                        $this->callPostAuthSuccessEvent($request, $guard, $passwordField);

                        $returnValue = $this->response->success($claim->getData());
                    } else { // Challenge generated
                        //Raise Post Auth Success Event
                        $this->callPostAuthSuccessEvent($request, null, $passwordField);

                        $returnValue = $this->response->success([]);
                    } //End if
                } else {
                    if ($claim===true) {
                        $request->session()->regenerate();

                        //Raise Post Auth Success Event
                        $this->callPostAuthSuccessEvent($request, $guard, $passwordField);

                        $returnValue = redirect(route(config('cognito.redirect_to_route_name', $this->redirectTo)));
                    } elseif ($claim===false) {
                        $returnValue = redirect()
                            ->back()
                            ->withInput($request->only($usernameField, 'remember'))
                            ->withErrors([
                                $usernameField => 'Incorrect username and/or password !!',
                            ]);
                    } else {
                        $returnValue = $claim;
                    }
                }
            } //End if

            return $returnValue;
        } catch(Exception $e) {
            Log::error('AuthController:processClaimResponse:Exception');
            throw $e;
        }
    } //Function ends

    private function callPreAuthEvent(Request $request, string $passwordField): void
    {
        //Raise pre registration event
        event(new PreAuthEvent(
            $request->except($passwordField),
            $request->ip()
        ));
    } //Function ends

    private function callPostAuthSuccessEvent(Request $request,
        string $guard, string $passwordField): void
    {
        //Raise Post Auth Success Event
        $user = Auth::guard($guard)->user();
        event(new PostAuthSuccessEvent(
            $user->toArray(),
            $request->except($passwordField),
            $request->ip()
        ));
    } //Function ends

    private function callPostAuthErrorEvent(Request $request,
        $e, string $passwordField): void
    {
        //Rise Post Auth Failed Event
        event(new PostAuthFailedEvent(
            $request->except($passwordField),
            $e, $request->ip()
        ));
    } //Function ends

} //Class ends
