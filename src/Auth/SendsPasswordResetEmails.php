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
use Illuminate\Foundation\Auth\SendsPasswordResetEmails as BaseSendsPasswordResetEmails;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

trait SendsPasswordResetEmails
{

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \string  $usernameKey (optional)
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request, string $usernameKey='email', bool $resetTypeCode=true)
    {
        $this->validateEmail($request);

        //Cognito reset link
        if ($this->sendCognitoResetLinkEmail($request[$usernameKey])) {
            if ($resetTypeCode) {
                return redirect(route('cognito.form.reset.password.code'));
            } else {
                return redirect(route('welcome'));
            } //End if            
        } //End if

        return $this->sendResetLinkFailedResponse($request, $response);
    } //Function ends


    /**
     * Send a cognito reset link to the given user.
     *
     * @param  \string  $username
     * @return \bool
     */
    public function sendCognitoResetLinkEmail(string $username)
    {
        //Send AWS Cognito reset link
        $response = app()->make(AwsCognitoClient::class)->sendResetLink($username);

        return ($response == Password::RESET_LINK_SENT);
    } //Function ends
    
} //Trait ends