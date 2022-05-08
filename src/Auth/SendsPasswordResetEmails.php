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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait SendsPasswordResetEmails
{

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Support\Collection  $request
     * @param  \string  $usernameKey (optional)
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(\Illuminate\Http\Request $request, string $usernameKey='email', bool $resetTypeCode=true, bool $isJsonResponse=false, array $attributes=null)
    {
        //Cognito reset link
        $response = $this->sendCognitoResetLinkEmail($request[$usernameKey], $attributes);

        //JSON Response
        if ($isJsonResponse) {
            return $response;
        } //End if

        //Action Response
        if ($response) {
            $routeCognito = Route::has('cognito.form.reset.password.code');

            if ($resetTypeCode && $routeCognito) {
                return redirect(route('cognito.form.reset.password.code'))
                    ->withInput($request->only($usernameKey))
                    ->with('success', true);
            } else {
                return redirect('/')
                    ->with('success', true);
            } //End if
        } else {
            return redirect()->back()
                ->withInput($request->only($usernameKey))
                ->withErrors([$usernameKey => 'cognito.invalid_user']);
        } //End if
    } //Function ends


    /**
     * Send a cognito reset link to the given user.
     *
     * @param  \string  $username
     * @return \bool
     */
    public function sendCognitoResetLinkEmail(string $username, array $clientMetadata=null)
    {
        $response = null;

        try {
            //Get existing user data from cognito
            $user = app()->make(AwsCognitoClient::class)->getUser($username);

            //Change the action based on user status
            switch ($user->get('UserStatus')) {
                case 'FORCE_CHANGE_PASSWORD':
                    //Check the config settings
                    if (config('cognito.allow_forgot_password_resend')) {
                        $attributes = [];

                        //Get cognito user attributes
                        $userAttributes = $user->get('UserAttributes');

                        //Build attributes based requirement
                        foreach ($userAttributes as $userAttribute) {
                            if ($userAttribute['Name'] != 'sub') {
                                $attributes[$userAttribute['Name']] = $userAttribute['Value'];
                            } //End if
                        } //Loop ends

                        $response = app()->make(AwsCognitoClient::class)->inviteUser($username, null, $attributes, $clientMetadata, 'RESEND');
                    } else {
                        throw new HttpException(400, 'The forgot password resend is disabled.');
                    } //End if
                    break;
                
                case 'CONFIRMED';
                default:
                    //Send AWS Cognito reset link
                    $response = app()->make(AwsCognitoClient::class)->sendResetLink($username, $clientMetadata);
                    break;
            } //End switch

            return ($response == Password::RESET_LINK_SENT);
        } catch (Exception $e) {
            return false;
        } //Try-catch ends
    } //Function ends
    
} //Trait ends