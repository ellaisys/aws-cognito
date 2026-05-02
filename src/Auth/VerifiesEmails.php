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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait VerifiesEmails
{
    /**
     * Private variable to indicate if the action
     * is called from controller
     */
    private bool $isControllerAction = false;

    /**
     * Private variable to indicate if the response
     * is to be in json format
     */
    private bool $isJsonResponse = false;

    /**
     * Private variable to indicate if the response
     * is to be raised as an exception
     */
    private bool $isRaiseException = false;

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array|null  $clientMetadata
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(Request $request, ?array $clientMetadata = null): mixed
    {
        try {
            // Initialize variables
            $returnValue = null;

            if(!$this->isJsonResponse) {
                $this->isJsonResponse = ($request->expectsJson() || $request->isJson());
            } //End if

            // If email is present in query parameters, encode it before validation and processing
            if ($request->query('email') ) {
                $email = urlencode($request->input('email'));
    
                //find %40 and replace with @ to avoid validation error
                $email = str_replace('%40', '@', $email);
                $request->merge(['email' => $email]);
            } //End if

            //Validate request
            $validator = Validator::make(
                $request->all(), $this->rules(['code' => 'required|numeric']));
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create data to save
            $payload = $request->only([
                'email', 'code'
            ]);

            //Call AWS Cognito to confirm user sign up
            $response = app()->make(AwsCognitoClient::class)->confirmUserSignUp(
                    $payload['email'], $payload['code'],
                    $clientMetadata
                );

            //Return response
            if ($this->isJsonResponse) {
                $returnValue = $this->isControllerAction ? $this->response->success($response) : $response;
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'Verification successful. Please login to continue.')
                    ->with('message', trans('messages.auth.registration_verification_success'));
            } //End if
        } catch (Exception $e) {
            Log::error('VerifiesEmails:verify:Exception');
            throw $e;
        } //End try

        return $returnValue;
    } //Function ends

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function resend(Request $request, ?array $clientMetadata = null): mixed
    {
        try {
            // Initialize variables
            $returnValue = null;

            if(!$this->isJsonResponse) {
                $this->isJsonResponse = ($request->expectsJson() || $request->isJson());
            } //End if

            // If email is present in query parameters, encode it before validation and processing
            if ($request->query('email') ) {
                $email = urlencode($request->input('email'));
    
                //find %40 and replace with @ to avoid validation error
                $email = str_replace('%40', '@', $email);
                $request->merge(['email' => $email]);
            } //End if

            //Validate request
            $validator = Validator::make(
                $request->all(), $this->rules());
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create data to save
            $payload = $request->only([
                'email', 'code'
            ]);

            $response = app()->make(AwsCognitoClient::class)->resendToken(
                    $payload['email'],
                    $clientMetadata
                );

            //Return response
            if ($this->isJsonResponse) {
                $returnValue = $this->isControllerAction ? $this->response->success($response) : $response;
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'Resend code request successful. Please verify your email.')
                    ->with('message', trans('messages.auth.registration_code_resend_success'));
            } //End if
        } catch (Exception $e) {
            Log::error('VerifiesEmails:resend:Exception');
            throw $e;
        } //End try

        return $returnValue;
    } //Function ends
    
    /**
     * Get the registration validation rules.
     *
     * @return array
     */
    public function rules(?array $moreRules = null): array
    {
        $rules = [
            'email' => 'required|email:rfc,dns|max:255'
        ];

        if ($moreRules) {
            $rules = array_merge($rules, $moreRules);
        }

        return $rules;
    } //Function ends

    /**
     * Set flag for action method called from controller
     *
     * @param bool $isControllerAction
     */
    protected function setIsControllerAction(bool $isControllerAction): void
    {
        $this->isControllerAction = $isControllerAction;

    }

    /**
     * Set flag if the response is to be in json format
     *
     * @param bool $isJsonResponse
     */
    protected function setIsJsonResponse(bool $isJsonResponse): void
    {
        $this->isJsonResponse = $isJsonResponse;
    }

    /**
     * Set flag if the response is to be raised as an exception
     * in case of errors
     *
     * @param bool $isRaiseException
     */
    protected function setIsRaiseException(bool $isRaiseException): void
    {
        $this->isRaiseException = $isRaiseException;
    }

} //Trait ends
