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

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoUserPool;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoUserStatusTypes;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait ConfirmsPasswords
{
    use BaseAuthTrait;

    /**
     * private variable for password policy
     */
    private $passwordPolicy = null;

    /**
     * Passed params
     */
    private $paramUsername = 'email';
    private $paramPasswordOld = 'password';
    private $paramPasswordNew = 'new_password';

    /**
     * Display the password confirmation view.
     *
     * @return \Illuminate\View\View
     */
    public function showConfirmForm()
    {
        return view('cognito::pages.auth.passwords.change');
    } // Function ends

    /**
     * Confirm the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $paramUsername
     * @param  string  $passwordOld
     * @param  string  $passwordNew
     *
     * @return mixed
     */
    final public function confirm(
        Request $request, string $paramUsername='email',
        string $passwordOld='password', string $passwordNew='new_password'): mixed
    {
        try
        {
            // Initialize variables
            $returnValue = null;

            // Assign params
            $this->paramUsername = $paramUsername;
            $this->paramPasswordOld = $passwordOld;
            $this->paramPasswordNew = $passwordNew;

            // Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            // Validate payload
            $validator = Validator::make($request->all(), $this->rules(), [
                'regex' => 'Must contain atleast '.$this->passwordPolicy['message'],
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            // Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            // Token Object
            $accessToken = $this->getAccessToken($request);

            // Change the user's password using the AWS Cognito client
            $response = $client->changePassword(
                $accessToken,
                $request[$passwordOld],
                $request[$passwordNew]
            );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                //Invalidate session
                $request->session()->invalidate();

                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.auth.password_change_success'))
                    ->with('data', $response);
            } //Return response

            return $returnValue;
        } catch(Exception $exception) {
            Log::error('ConfirmsPasswords:confirm:Exception');
            throw $exception;
        } //Try-catch ends
    } // Function ends

    /**
     * Get the password confirmation validation rules.
     *
     * @return array
     */
    public function rules(): array
    {
        try {
            return [
                $this->paramUsername => 'sometimes|email',
                $this->paramPasswordOld => ['required', 'regex:'.$this->passwordPolicy['regex']],
                $this->paramPasswordNew => ['required', 'confirmed', 'regex:'.$this->passwordPolicy['regex']],
            ];
        } catch (Exception $e) {
            Log::error('ConfirmsPasswords:rules:Exception');
            throw $e;
        } //End try
    } // Function ends
} // Trait ends
