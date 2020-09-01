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
use Illuminate\Support\Facades\Password;

use Ellaisys\Cognito\AwsCognitoClient;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails as BaseSendsPasswordResetEmails;

trait SendPasswordResetEmailTrait
{
    use BaseSendsPasswordResetEmails;

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validateEmail($request);

        $response = app()->make(AwsCognitoClient::class)->sendResetLink($request->email);

        if ($response == Password::RESET_LINK_SENT) {
            return redirect(route('aws-cognito.password-reset'));
        }

        return $this->sendResetLinkFailedResponse($request, $response);
    } //Function ends
    
} //Trait ends