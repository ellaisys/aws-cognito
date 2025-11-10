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

use Ellaisys\Cognito\Auth\RegistersUsers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Events\Auth\PreRegistrationEvent;
use Ellaisys\Cognito\Events\Auth\PostRegistrationEvent;

use Exception;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Client metadata to be sent to AWS Cognito
     *
     * @var array|null
     */
    protected $clientMetadata = null;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except(['actionInvite']);

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(true);

        parent::__construct();
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        return User::create($data);
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        return $this->redirectTo;
    }

    /**
     * Action to invite the a new user
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function actionRegister(Request $request)
    {
        try {
            //Raise pre registration event
            $this->callPreRegistrationEvent($request);

            //Validate request and get registration data
            $user = $this->register($request, $this->clientMetadata, false);
            if ($user) {
                //Raise post registration event
                $this->callPostRegistrationEvent($request, $user);

                return $this->response->success($user);
            } //End if
        } catch (Exception $e) {
            Log::error('RegisterController:actionRegister:Exception');
            throw $e;
        } //End try-catch
    } //Function ends

    /**
     * Action to invite the a new user
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function actionInvite(Request $request)
    {
        try {
            //Raise pre registration event
            $this->callPreRegistrationEvent($request);

            //Validate request and get registration data
            $user = $this->invite($request, $this->clientMetadata);
            if ($user) {
                //Raise post registration event
                $this->callPostRegistrationEvent($request, $user);

                return $this->response->success($user);
            } //End if
        } catch (Exception $e) {
            Log::error('RegisterController:actionInvite:Exception');
            throw $e;
        } //End try-catch
    } //Function ends



    private function callPreRegistrationEvent(Request $request): void
    {
        //Raise pre registration event
        event(new PreRegistrationEvent(
            $this->registrationType,
            $request->except('password'),
            $request->ip()
        ));
    } //Function ends

    private function callPostRegistrationEvent(Request $request, array $user): void
    {
        //Raise post registration event
        event(new PostAuthSuccessEvent(
            $this->registrationType,
            $user,
            $request->except('password'),
            $request->ip()
        ));
    } //Function ends

} //Class ends
