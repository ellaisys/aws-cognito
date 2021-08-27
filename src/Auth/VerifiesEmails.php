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

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait VerifiesEmails
{ 

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Support\Collection  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Collection $request)
    {

        $validator = Validator::make($request, [
            'email' => 'required|email', 
            'confirmation_code' => 'required|numeric',
        ]);

        $response = app()->make(AwsCognitoClient::class)->confirmUserSignUp($request['email'], $request['confirmation_code']);

        if ($response == 'validation.invalid_user') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'cognito.validation.invalid_user']);
        }

        if ($response == 'validation.invalid_token') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['confirmation_code' => 'cognito.validation.invalid_token']);
        }

        if ($response == 'validation.exceeded') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['confirmation_code' => 'cognito.validation.exceeded']);
        }

        if ($response == 'validation.confirmed') {
            return redirect($this->redirectPath())->with('verified', true);
        }

        return redirect($this->redirectPath())->with('verified', true);
    } //Function ends


    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Support\Collection  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Collection $request)
    {

        $response = app()->make(AwsCognitoClient::class)->resendToken($request->email);

        if ($response == 'validation.invalid_user') {
            return response()->json(['error' => 'cognito.validation.invalid_user'], 400);
        }

        if ($response == 'validation.exceeded') {
            return response()->json(['error' => 'cognito.validation.exceeded'], 400);
        }

        if ($response == 'validation.confirmed') {
            return response()->json(['error' => 'cognito.validation.confirmed'], 400);
        }

        return response()->json(['success' => 'true']);
    } //Function ends
    
} //Trait ends