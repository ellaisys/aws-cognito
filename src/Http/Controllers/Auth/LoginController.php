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

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;

use Ellaisys\Cognito\Events\Auth\PreAuthEvent;
use Ellaisys\Cognito\Events\Auth\PostAuthSuccessEvent;
use Ellaisys\Cognito\Events\Auth\PostAuthFailedEvent;

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

    private $usernameField = 'username';
    private $passwordField = 'password';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['actionLogout', 'actionLogoutForced']);

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(true);

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
        string $usernameField='username', string $passwordField='password',
        ?CognitoAuthFlowTypes $authFlow = CognitoAuthFlowTypes::USER_PASSWORD_AUTH)
    {
        try {
            //Initialize parameters
            $claim = null;
            $guard = 'web';
            $isJsonResponse = false;
            $this->usernameField = $usernameField;
            $this->passwordField = $passwordField;

            //Raise Pre Auth Event
            $this->callPreAuthEvent($request);

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Authenticate with Cognito Package Trait based on the guard
            if (in_array($authFlow, [CognitoAuthFlowTypes::USER_PASSWORD_AUTH,
                CognitoAuthFlowTypes::ADMIN_USER_PASSWORD_AUTH]))
            {
                $claim = $this->attemptLogin($request, $authFlow,
                    $usernameField, $passwordField);
            } elseif ($authFlow === CognitoAuthFlowTypes::USER_SRP_AUTH) {
                $claim = $this->attemptLoginSRP($request, $authFlow,
                    $usernameField, $passwordField);
            } else {
                throw new HttpException(400, 'Invalid authentication flow type specified');
            } //End if

            //Process the claim response
            return $this->processClaimResponse(
                    $request, $claim, $guard, $isJsonResponse,
                    $usernameField, $passwordField
                );
        } catch(Exception $e) {
            Log::error('LoginController:login:Exception');

            //Rise Post Auth Failed Event
            $this->callPostAuthErrorEvent($request, $e, $passwordField);

            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Authenticate User with SRP authentication flow
     * @param \Illuminate\Http\Request $request
     * @param string $usernameField
     * @param string $passwordField
     *
     * @throws \HttpException
     *
     * @return mixed
     */
    public function loginSRP(Request $request,
        string $usernameField='username', string $passwordField='srp_a')
    {
        try {
            return $this->login(
                    $request,
                    $usernameField, $passwordField,
                    CognitoAuthFlowTypes::USER_SRP_AUTH
                );
        } catch(Exception $e) {
            Log::error('LoginController:loginSRP:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Challenge based authentication action
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \HttpException
     */
    public function actionChallenge(Request $request)
    {
        try
        {
            //Initialize parameters
            $guard = $this->getGuard($request);
            $isJsonResponse = $this->getIsJsonResponse($request);

            //Authenticate the user request
            $response = $this->challenge($request);

            //Process the claim response
            return $this->processClaimResponse(
                    $request, $response, $guard, $isJsonResponse,
                    $this->usernameField, $this->passwordField
                );
        } catch (Exception $e) {
            Log::error('LoginController:actionChallenge:Exception');

            //Rise Post Auth Failed Event
            $this->callPostAuthErrorEvent($request, $e, $this->passwordField);

            throw $e;
        } //try-catch ends
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
            //Initialize parameters
            $returnValue = null;

            //Logout user
            $response = $this->logout($request, $forced);

            //Send response data
            if ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route('cognito.form.login')
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.auth.logout_success'))
                    ->with('data', $response);
            } //End if
        } catch (Exception $e) {
            Log::error('LoginController:actionLogout:Exception');
            throw $e;
        } //End try-catch
        
        return $returnValue;
    } //Function ends

    /**
     * Forced logout action for the API based approach.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function actionLogoutForced(Request $request)
    {
        return $this->actionLogout($request, true);
    } //Function ends

    /**
     * Process the claim response from Cognito authentication.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $claim
     * @param string $guard
     * @param bool $isJsonResponse
     * @return mixed
     */
    private function processClaimResponse(Request $request, $claim,
        string $guard, bool $isJsonResponse): mixed
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
                        $this->callPostAuthSuccessEvent($request, $guard);

                        $returnValue = $this->response->success($claim->getData());
                    } else { // Challenge generated
                        $returnValue = $this->response->success($claim);
                    } //End if
                } else {
                    if ($claim===true) {
                        $request->session()->regenerate();

                        //Raise Post Auth Success Event
                        $this->callPostAuthSuccessEvent($request, $guard);

                        $returnValue = redirect()
                            ->route(config('cognito.redirect_to_route_name', 'cognito.home'))
                            ->with('status', 'success')
                            ->with('message', trans('cognito::messages.auth.login_success'));
                    } elseif ($claim===false) {
                        $returnValue = redirect()
                            ->back()
                            ->withInput($request->only($this->usernameField, 'remember'))
                            ->withErrors([
                                $this->usernameField => trans('cognito::messages.auth.error.incorrect_credentials'),
                            ]);
                    } elseif (is_array($claim)) { // Challenge generated
                        $returnValue = redirect()
                            ->route('cognito.form.login.step', [
                                    'step' => 'challenge',
                                    'challenge' => $claim['challenge_name']
                                ])
                            ->with('status', 'success')
                            ->with('message', trans('cognito::messages.auth.challenge_generated'))
                            ->with('data', $claim);
                    } else {
                        $returnValue = $claim;
                    }
                }
            } //End if

            return $returnValue;
        } catch(Exception $e) {
            Log::error('LoginController:processClaimResponse:Exception');
            throw $e;
        }
    } //Function ends

    /**
     * Call the pre-authentication event.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function callPreAuthEvent(Request $request): void
    {
        //Raise pre registration event
        event(new PreAuthEvent(
            $request->except($this->passwordField),
            $request->ip()
        ));
    } //Function ends

    /**
     * Call the post-authentication success event.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $guard
     * @return void
     */
    private function callPostAuthSuccessEvent(
        Request $request, string $guard): void
    {
        //Raise Post Auth Success Event
        $user = Auth::guard($guard)->user();
        event(new PostAuthSuccessEvent(
            $user->toArray(),
            $request->except($this->passwordField),
            $request->ip()
        ));
    } //Function ends

    /**
     * Call the post-authentication error event.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     * @return void
     */
    private function callPostAuthErrorEvent(
        Request $request, Exception $e): void
    {
        //Rise Post Auth Failed Event
        event(new PostAuthFailedEvent(
            $request->except($this->passwordField),
            $e, $request->ip()
        ));
    } //Function ends

} //Class ends
