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

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

trait ResetsPasswords
{

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $paramToken (optional)
     * @param  string  $passwordNew (optional)
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request, string $paramUsername='email', string $paramToken='token', string $passwordNew='password')
    {
        $response = '';
        try {
            if ($request instanceof Request) {
                $req = $request;

                //Validate request
                $validator = Validator::make($request->all(), $this->rules());

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                } //End if

                $request = collect($request->all());
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get User Data
            $user = $client->getUser($request[$paramUsername]);

            //Check user status and change password
            if (($user['UserStatus'] == AwsCognitoClient::USER_STATUS_CONFIRMED) ||
                ($user['UserStatus'] == AwsCognitoClient::RESET_REQUIRED_PASSWORD)) {
                $response = $client->resetPassword($request[$paramToken], $request[$paramUsername], $request[$passwordNew]);
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
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['email' => $request->email]
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
            'token'    => 'required_without:code',
            'code'     => 'required_without:token',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    } //Function ends

} //Trait ends