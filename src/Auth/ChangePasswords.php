<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Auth;

use Illuminate\Http\Request;
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

trait ChangePasswords
{
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
     * Change the given user's password.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $passwordOld (optional)
     * @param  string  $passwordNew (optional)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset($request, string $paramUsername='email', string $passwordOld='password', string $passwordNew='new_password')
    {
        try {
            //Assign params
            $this->paramUsername = $paramUsername;
            $this->paramPasswordOld = $passwordOld;
            $this->paramPasswordNew = $passwordNew;

            //Transform to collection
            if ($request instanceof Request) {
                $request = collect($request->all());
            } //End if

            //Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($request->all(), $this->rulesChangePassword(), [
                'regex' => 'Must contain atleast '.$this->passwordPolicy['message'],
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get User Data
            $user = $client->getUser($request[$paramUsername]);

            if (empty($user)) {
                throw new InvalidUserException('cognito.validation.reset_required.invalid_user');
            } //End if

            //Action based on User Status
            switch ($user['UserStatus']) {
                case AwsCognitoClient::FORCE_CHANGE_PASSWORD:
                    $response = $this->forceNewPassword($client, $request, $paramUsername, $passwordOld, $passwordNew);
                    break;

                case AwsCognitoClient::RESET_REQUIRED_PASSWORD:
                    throw new AwsCognitoException('cognito.validation.reset_required.invalid_request');
                    break;

                default:
                    $response = $this->changePassword($client, $request, $paramUsername, $passwordOld, $passwordNew);
                    break;
            } //End switch

            return $response;
        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        } //Try Catch ends
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
    private function forceNewPassword(AwsCognitoClient $client, $request, string $paramUsername, string $passwordOld, string $passwordNew)
    {
        //Authenticate user
        $login = $client->authenticate($request[$paramUsername], $request[$passwordOld]);

        return $client->confirmPassword($request[$paramUsername], $request[$passwordNew], $login->get('Session'));
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
    private function changePassword(AwsCognitoClient $client, $request, string $paramUsername, string $passwordOld, string $passwordNew)
    {
        //Authenticate user
        $cognitoUser = $client->authenticate($request[$paramUsername], $request[$passwordOld]);
        $accessToken = $cognitoUser['AuthenticationResult']['AccessToken'];

        if (empty($accessToken)) {
            throw new NoTokenException('cognito.validation.reset_required.no_token');
        } //End if

        return $client->changePassword($accessToken, $request[$passwordOld], $request[$passwordNew]);
    } //Function ends


    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showChangePasswordForm(Request $request, $token = null)
    {
        return view('vendor.ellaisys.aws-cognito.reset-password')
            ->with([
                'token' => $token,
                $this->paramUsername => $request->email
            ]);
    } //Function ends


    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rulesChangePassword()
    {
        try {
            return [
                $this->paramUsername => 'required|email',
                $this->paramPasswordOld => 'required|regex:'.$this->passwordPolicy['regex'],
                $this->paramPasswordNew => 'required|confirmed|regex:'.$this->passwordPolicy['regex'],
            ];
        } catch (Exception $e) {
            throw $e;
        } //End try
    } //Function ends

} //Trait ends
