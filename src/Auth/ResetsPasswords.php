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
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

trait ResetsPasswords
{
    /**
     * private variable for password policy
     */
    private $passwordPolicy = null;

    /**
     * Passed params
     */
    private $paramToken = 'token';
    private $paramCode = 'code';
    private $paramUsername = 'email';
    private $paramPassword = 'password';

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $paramToken (optional)
     * @param  string  $paramPassword (optional)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request,
        string $paramUsername='email', string $paramToken='token',
        string $paramPassword='password')
    {
        $response = '';
        try {
            //Assign params
            $this->paramUsername = $paramUsername;
            $this->paramToken = $paramToken;
            $this->paramPassword = $paramPassword;

            if ($request instanceof Request) {
                $req = $request;
                $request = collect($request->all());
            } //End if

            //Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($request->all(), $this->rules(), [
                'regex' => $this->passwordPolicy['message'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get User Data
            $user = $client->getUser($request[$paramUsername]);

            //Check user status and change password
            if (($user['UserStatus'] == AwsCognitoClient::USER_STATUS_CONFIRMED) ||
                ($user['UserStatus'] == AwsCognitoClient::RESET_REQUIRED_PASSWORD)) {
                $response = $client->resetPassword($request[$paramToken], $request[$paramUsername], $request[$paramPassword]);
            } else {
                $response = false;
            } //End if

        } catch(Exception $e) {
            return $this->sendResetFailedResponse($req, $e->getMessage());
        } //Try-Catch ends

        return $this->sendResetResponse($req, $response);
    } //Function ends

    /**
     * Get the response for a successful password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => trans($response)], 200);
        } //End if

        return redirect($this->redirectPath())
            ->with('status', trans($response));
    } //Function ends

    /**
     * Get the response for a failed password reset.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        } //End if

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($response)]);
    } //Function ends

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        } //End if

        return property_exists($this, 'redirectTo') ? $this->redirectTo : '/home';
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
    public function showResetForm(Request $request, string $token = null)
    {
        return view('cognito.form.password.reset')->with(
            [
                'email' => $request->email,
                'token' => $token
            ]
        );
    } //Function ends

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            $this->paramToken       => 'required_without:'.$this->paramCode,
            $this->paramCode        => 'required_without:'.$this->paramToken,
            $this->paramUsername    => 'required|email',
            $this->paramPassword    => 'required|confirmed|regex:'.$this->passwordPolicy['regex'],
        ];
    } //Function ends

} //Trait ends
