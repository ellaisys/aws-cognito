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
use Ellaisys\Cognito\Auth\SendsPasswordResetEmails;

use Exception;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Send reset link for the forgot password
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function sendResetLink(Request $request)
    {
        try {
            //Initialize parameters
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
            } //End if

            //Request reset link
            $response = $this->sendResetLinkEmail($request, 'email', true, $isJsonResponse);

            if ($isJsonResponse) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = $response;
            } //End if
            return $returnValue;
        } catch (Exception $e) {
            Log::error('SendsPasswordResetEmails:sendResetLinkEmail:Exception');
            throw $e;
        } //End try-catch
    } //Function ends

} //Class ends
