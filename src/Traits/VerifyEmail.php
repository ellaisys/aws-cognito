<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Traits;

use Illuminate\Http\Request;
use Ellaisys\Cognito\AwsCognitoClient;
use Illuminate\Foundation\Auth\VerifiesEmails as BaseVerifiesEmails;

trait VerifyEmail
{
    use BaseVerifiesEmails;

    /**
     * Show the email verification notice.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show(Request $request)
    {
        return view('black-bits/laravel-cognito-auth::verify');
    } //Function ends
    

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request)
    {
        $request->validate(['email' => 'required|email', 'confirmation_code' => 'required|numeric']);

        $response = app()->make(AwsCognitoClient::class)->confirmUserSignUp($request->email, $request->confirmation_code);

        if ($response == 'validation.invalid_user') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => trans('black-bits/laravel-cognito-auth::validation.invalid_user')]);
        }

        if ($response == 'validation.invalid_token') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['confirmation_code' => trans('black-bits/laravel-cognito-auth::validation.invalid_token')]);
        }

        if ($response == 'validation.exceeded') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['confirmation_code' => trans('black-bits/laravel-cognito-auth::validation.exceeded')]);
        }

        if ($response == 'validation.confirmed') {
            return redirect($this->redirectPath())->with('verified', true);
        }

        return redirect($this->redirectPath())->with('verified', true);
    } //Function ends


    /**
     * Resend the email verification notification.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $response = app()->make(AwsCognitoClient::class)->resendToken($request->email);

        if ($response == 'validation.invalid_user') {
            return response()->json(['error' => trans('black-bits/laravel-cognito-auth::validation.invalid_user')], 400);
        }

        if ($response == 'validation.exceeded') {
            return response()->json(['error' => trans('black-bits/laravel-cognito-auth::validation.exceeded')], 400);
        }

        if ($response == 'validation.confirmed') {
            return response()->json(['error' => trans('black-bits/laravel-cognito-auth::validation.confirmed')], 400);
        }

        return response()->json(['success' => 'true']);
    } //Function ends
    
} //Trait ends