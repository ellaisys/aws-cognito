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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums\CognitoUserStatusTypes;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait SendsPasswordResetEmails
{
    use BaseAuthTrait;

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \string  $usernameKey (optional)
     * @param  \array|null  $clientMetadata (optional)
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function sendResetLinkEmail(Request $request,
        string $usernameKey = 'email', ?array $clientMetadata = null): mixed
    {
        try {
            //Initialize variables
            $returnValue = null;

            //Validate request
            $validator = Validator::make($request->all(), [
                $usernameKey => 'required|email:rfc,dns|max:255',
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Cognito reset link
            $response = $this->sendCognitoResetLinkEmail($request[$usernameKey], $clientMetadata);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = $this->getWebResponse(
                        $request, $response, $usernameKey
                    );
            } //Return response
        } catch (Exception $e) {
            Log::error('SendsPasswordResetEmails:sendResetLinkEmail:Exception');
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Get the response for the reset link email action.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \array  $response
     * @param  \string  $usernameKey
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    private function getWebResponse(Request $request, $response,
        string $usernameKey): RedirectResponse
    {
        //Initialize variables
        $returnValue = null;

        //Action Response
        if ($response && $response['response']) {
            if (Route::has(config('cognito.routes.web.password_reset_page')) &&
                (CognitoUserStatusTypes::from($response['status']) != CognitoUserStatusTypes::FORCE_CHANGE_PASSWORD)) {

                $returnValue = redirect()
                    ->route(config('cognito.routes.web.password_reset_page'))
                    ->withInput($request->only($usernameKey))
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.auth.password_reset_success'))
                    ->with('data', $response);
            } else {
                $returnValue = redirect('/')
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.auth.password_reset_success'))
                    ->with('data', $response);
            } //End if
        } else {
            $returnValue = redirect()
                ->back()
                ->withInput($request->only($usernameKey))
                ->withErrors([$usernameKey => 'cognito.invalid_user']);
        } //End if

        return $returnValue;
    } //Function ends

    /**
     * Send a cognito reset link to the given user.
     *
     * @param  \string  $username
     * @return \bool
     */
    private function sendCognitoResetLinkEmail(string $username, ?array $clientMetadata = null): array
    {
        // Initialize variables
        $response = null;
        $returnValue = false;

        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get existing user data from cognito
            $user = $client->adminGetUser($username);
            
            if ($user) {
                //Change the action based on user status
                switch (CognitoUserStatusTypes::from($user->get('UserStatus'))) {
                    case CognitoUserStatusTypes::FORCE_CHANGE_PASSWORD:
                        $returnValue = $this->resendInvite($user, $clientMetadata);
                        break;
                    
                    case CognitoUserStatusTypes::RESET_REQUIRED;
                    case CognitoUserStatusTypes::CONFIRMED;
                    default:
                        //Send AWS Cognito reset link
                        $response = $client->sendResetLink($username, $clientMetadata);
                        $returnValue = ($response == Password::RESET_LINK_SENT);
                        break;
                } //End switch

                return [
                    'status' => $user->get('UserStatus'),
                    'response' => $returnValue
                ];
            } else {
                throw new InvalidUserException('The user does not exist.');
            } //End if
        } catch (Exception $e) {
            Log::error('SendsPasswordResetEmails:sendCognitoResetLinkEmail:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Resend the invite to the given user.
     *
     * @param  \Illuminate\Support\Collection  $user
     * @param  \array|null  $clientMetadata (optional)
     *
     * @return \bool
     */
    private function resendInvite($user, ?array $clientMetadata = null): bool
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);
            
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

                $response = $client->inviteUser(
                    $user->get('Username'), null, $attributes,
                    $clientMetadata, 'RESEND'
                );
                return !empty($response);
            } else {
                throw new HttpException(400, 'The forgot password resend is disabled.');
            } //End if
        } catch (Exception $exception) {
            Log::error('SendsPasswordResetEmails:resendInvite:Exception');
            throw $exception;
        } //Try-catch ends
    } //Function ends
    
} //Trait ends
