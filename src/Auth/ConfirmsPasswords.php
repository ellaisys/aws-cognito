<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Auth;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoUserPool;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

trait ConfirmsPasswords
{
    /**
     * private variable for Cognito User
     */
    private $authData = null;

    /**
     * private variable for password policy
     */
    private $passwordPolicy = null;

    /**
     * Passed params
     */
    private $paramUsername = 'email';
    private $paramPasswordOld = 'password';
    private $paramPasswordNew = 'new_password';

    /**
     * Display the password confirmation view.
     *
     * @return \Illuminate\View\View
     */
    public function showConfirmForm()
    {
        return view('auth.passwords.confirm');
    }

    /**
     * Confirm the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function confirm(
        Request $request, string $guard='web',
        string $paramUsername='email',
        string $passwordOld='password', string $passwordNew='new_password')
    {
        try
        {
            //Initialize parameters
            $returnValue = null;

            //Assign params
            $this->paramUsername = $paramUsername;
            $this->paramPasswordOld = $passwordOld;
            $this->paramPasswordNew = $passwordNew;

            //Transform to collection
            if ($request instanceof Request) {
                $payload = collect($request->all());
            } //End if

            //Get User data from Guard
            $claim = Auth::guard($guard)->getClaim();
            if (empty($claim)) { throw new InvalidUserException(); }
            $payload = $payload->replace([$paramUsername => $claim['username']]);

            //Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($payload->all(), $this->rules(), [
                'regex' => 'Must contain atleast '.$this->passwordPolicy['message'],
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get User Data
            $this->authData = $client->getUser($payload[$paramUsername]);
            if (empty($this->authData)) {
                throw new InvalidUserException(AwsCognitoException::COGNITO_USER_INVALID);
            } //End if

            //Action based on User Status
            switch ($this->authData['UserStatus']) {
                case AwsCognitoClient::FORCE_CHANGE_PASSWORD:
                    $returnValue = $this->forceNewPassword(
                        $client, $guard, $request,
                        $paramUsername, $passwordOld, $passwordNew
                    );
                    break;

                case AwsCognitoClient::RESET_REQUIRED_PASSWORD:
                    throw new AwsCognitoException(AwsCognitoException::COGNITO_RESET_PWD_REQ_INVALID);
                    break;

                default:
                    $returnValue = $this->changePassword(
                        $client, $guard, $payload,
                        $passwordOld, $passwordNew
                    );
                    break;
            } //End switch

            return $returnValue;
        } catch(Exception $e) {
            Log::error('ConfirmsPasswords:confirm:Exception', ['$e' => $e]);
            if ($e instanceof CognitoIdentityProviderException) {
                throw AwsCognitoException::create($e);
            } //End if
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * If a user is being forced to set a new password for the first time follow that flow instead.
     *
     * @param  \Ellaisys\Cognito\AwsCognitoClient  $client
     * @param  \Illuminate\Support\Collection  $request
     * @param  string  $paramUsername
     * @param  string  $passwordOld
     * @param  string  $passwordNew
     *
     * @return string
     */
    private function forceNewPassword(
        AwsCognitoClient $client, string $guard,
        Collection $payload, string $paramUsername,
        string $passwordOld, string $passwordNew)
    {
        //Authenticate user
        $login = $client->authenticate(
            $payload[$paramUsername],
            $payload[$passwordOld]
        );

        //Confirm new password for the user
        $response = $client->confirmPassword(
            $payload[$paramUsername],
            $payload[$passwordNew],
            $login->get('Session')
        );
        if ($response === Password::PASSWORD_RESET) {
            //Update the user attributes
            $responseAttributesUpdate = $client->setUserAttributes(
                $payload[$paramUsername], [
                'email_verified' => 'true',
            ]);

            if ($responseAttributesUpdate) {
                return $response;
            } else {
                throw new AwsCognitoException(AwsCognitoException::COGNITO_RESET_PWD_FAILED);
            } //End if
        } else {
            throw new AwsCognitoException(AwsCognitoException::COGNITO_RESET_PWD_REQ_INVALID);
        } //End if
    } //Function ends

    /**
     * Method to change the password for the currently signed-in user.
     *
     * @param  \Ellaisys\Cognito\AwsCognitoClient  $client
     * @param  string  $guard
     * @param  \Illuminate\Support\Collection  $request
     * @param  string  $passwordOld
     * @param  string  $passwordNew
     *
     * @return string
     */
    private function changePassword(
        AwsCognitoClient $client, string $guard,
        Collection $payload, string $passwordOld, string $passwordNew)
    {
        try
        {
            //Get Authenticated user
            $authUser = Auth::guard($guard)->user();
            if (empty($authUser)) { throw new InvalidUserException(); }

            //AccessToken Object
            $claim = Auth::guard($guard)->getClaim();
            if (empty($claim)) { throw new InvalidUserException(); }
            $accessToken = $claim['token'];

            return $client->changePassword(
                $accessToken,
                $payload[$passwordOld],
                $payload[$passwordNew]
            );
        } catch(Exception $e) {
            Log::error('ConfirmsPasswords:confirm:Exception');
            if ($e instanceof CognitoIdentityProviderException) {
                throw AwsCognitoException::create($e);
            } //End if
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Get the password confirmation validation rules.
     *
     * @return array
     */
    public function rules()
    {
        try {
            return [
                $this->paramUsername => 'sometimes|email',
                $this->paramPasswordOld => 'required|regex:'.$this->passwordPolicy['regex'],
                $this->paramPasswordNew => 'required|confirmed|regex:'.$this->passwordPolicy['regex'],
            ];
        } catch (Exception $e) {
            Log::error('ConfirmsPasswords:rules:Exception');
            throw $e;
        } //End try
    } //Function ends

} //Trait ends
