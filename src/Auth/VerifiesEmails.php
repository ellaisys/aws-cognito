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
    use BaseAuthTrait;

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array|null  $clientMetadata
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verify(Request $request, ?array $clientMetadata = null): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            // If email is present in query parameters, decode it before validation and processing
            $email = $this->getDataFromQueryParam($request, 'email', EncryptionTypes::DEFAULT, true);
            if (!empty($email)) {
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
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'success')
                    ->with('username', $payload['email'])
                    ->with('session', $response['Session'] ?? null)
                    ->with('message', trans('cognito::messages.auth.registration_verification_success'));
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
        // Initialize variables
        $returnValue = null;

        try {
            // If email is present in query parameters, decode it before validation and processing
            $email = $this->getDataFromQueryParam($request, 'email', EncryptionTypes::DEFAULT, true);
            if (!empty($email)) {
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

            $response = app()->make(AwsCognitoClient::class)->resendConfirmationCode(
                    $payload['email'],
                    $clientMetadata
                );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'success')
                    ->with('username', $payload['email'])
                    ->with('message', trans('cognito::messages.auth.registration_code_resend_success'));
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
        } //End if

        return $rules;
    } //Function ends

} //Trait ends
