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
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords as BaseResetsPasswords;

use Ellaisys\Cognito\AwsCognitoClient;

trait ResetPassword
{
    use BaseResetsPasswords;

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        $client = app()->make(AwsCognitoClient::class);

        $user = $client->getUser($request->email);

        if ($user['UserStatus'] == AwsCognitoClient::FORCE_CHANGE_PASSWORD) {
            $response = $this->forceNewPassword($request);
        } else {
            $response = $client->resetPassword($request->token, $request->email, $request->password);
        }

        return $response == Password::PASSWORD_RESET
            ? $this->sendResetResponse($request, $response)
            : $this->sendResetFailedResponse($request, $response);
    } //Function ends


    /**
     * If a user is being forced to set a new password for the first time follow that flow instead.
     *
     * @param  \Illuminate\Http\Request $request
     * @return string
     */
    private function forceNewPassword($request)
    {
        $client = app()->make(AwsCognitoClient::class);
        $login = $client->authenticate($request->email, $request->token);

        return $client->confirmPassword($request->email, $request->password, $login->get('Session'));
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
        return view('vendor.black-bits.laravel-cognito-auth.reset-password')->with(
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
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:8',
        ];
    } //Function ends

} //Trait ends