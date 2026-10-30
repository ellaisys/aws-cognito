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

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;

use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $this->setIsControllerAction(false);

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
            $this->usernameField = $usernameField;
            $this->passwordField = $passwordField;

            //Authenticate with Cognito Package Trait based on the guard
            if (in_array($authFlow, [CognitoAuthFlowTypes::USER_PASSWORD_AUTH,
                CognitoAuthFlowTypes::ADMIN_USER_PASSWORD_AUTH]))
            {
                return $this->attemptLogin($request, $authFlow,
                    $usernameField, $passwordField);
            } elseif ($authFlow === CognitoAuthFlowTypes::USER_SRP_AUTH) {
                return $this->attemptLoginSRP($request, $authFlow,
                    $usernameField, $passwordField);
            } else {
                throw new HttpException(400, 'Invalid authentication flow type specified');
            } //End if
        } catch(Exception $exception) {
            Log::error('LoginController:login:Exception');
            throw $exception;
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
        return $this->login(
                $request,
                $usernameField, $passwordField,
                CognitoAuthFlowTypes::USER_SRP_AUTH
            );
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
        //Authenticate the user request
        return $this->challenge($request);
    } //Function ends

    /**
     * Logout action for the API based approach.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function actionLogout(Request $request, bool $forced = false)
    {
        //Logout user
        return $this->logout($request, $forced);
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

} //Class ends
