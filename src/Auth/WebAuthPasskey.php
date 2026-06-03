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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;

use Ellaisys\Cognito\Events\Auth\PostPasskeyCompleteEvent;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait WebAuthPasskey
{
    use BaseAuthTrait;

    /**
     * Action to start registration of a passkey authenticator for the currently signed-in user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            //Get the response from AWS Cognito for starting passkey registration
            $response = $client->startWebAuthnRegistration($accessToken);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:start:Exception');
            throw $e;
        }

        return $returnValue;
    } //Function ends

    /**
     * Action to complete registration of a passkey authenticator for the currently signed-in user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Validate payload
            $validator = Validator::make($request->all(), [
                'credential' => ['required']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            //Get the response from AWS Cognito for completing passkey registration
            $response = $client->completeWebAuthnRegistration(
                $accessToken,
                json_decode($request['credential'], true)
            );

            //Get Authenticated user
            $model = $this->getAuthenticatedUser($request);
            if (method_exists($model, 'hasPasskeyTrait')) {
                $model->is_webauthn_enabled = true;
                $model->save();
            } //End if

            //Fire PostPasskeyCompleteEvent
            event(new PostPasskeyCompleteEvent(
                    $model->toArray(),
                    $response->toArray(), $request->ip()
                ));

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:complete:Exception');
            throw $e;
        }

        return $returnValue;
    } //Function ends

    /**
     * Action to authenticate by responding to the passkey authentication challenge
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function challenge(Request $request,
        ?string $challengeName = null,
        ?string $paramUsername='username',
        ?string $paramPassword='')
    {
        try {
            // Initialize variables
            $returnValue = null;
            $guard = $this->getGuard($request);

            if (!empty($challengeName)) {
                $request->merge(['challenge_name' => $challengeName]);
            } //End if

            // If username present in query parameters is email, decode it before validation and processing
            $email = $this->getDataFromQueryParam($request, $paramUsername, EncryptionTypes::URL_ENCODE, true);
            if (!empty($email)) {
                $request->merge([$paramUsername => $email]);
            } //End if

            //Convert challenge name to upper case if present in the request
            if ($request->has('challenge_name')) {
                $request->merge(['challenge_name' => strtoupper($request['challenge_name'])]);
            } //End if
        
            //Validate payload
            $validator = Validator::make($request->all(), [
                $paramUsername => ['required'],
                'challenge_name' => ['sometimes', 'in:WEB_AUTHN,EMAIL_OTP,SMS_OTP']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Authenticate User
            $response = Auth::guard($guard)->attempt(
                    $request->all(), false,
                    $paramUsername, $paramPassword,
                    CognitoAuthFlowTypes::USER_AUTH
                );

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:challenge:Exception');
            throw $e;
        }

        return $returnValue;
    } //Function ends

    /**
     * Action to delete a registered passkey authenticator for the currently signed-in user.
     * TO BE TESTED
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            // Initialize variables
            $returnValue = null;

            //Validate payload
            $validator = Validator::make($request->all(), [
                'credential_id' => ['required']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Token Object
            $accessToken = $this->getAccessToken($request);

            //Get the response from AWS Cognito for deleting the passkey authenticator
            $response = $client->deleteWebAuthnCredential(
                $accessToken,
                $request['credential_id']
            );

            //Get Authenticated user
            $model = $this->getAuthenticatedUser($request);
            if (method_exists($model, 'hasPasskeyTrait') && $model->hasPasskeyTrait()) {
                $model->is_webauthn_enabled = false;
                $model->save();
            } //End if

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:delete:Exception');
            throw $e;
        } //End try

        return $returnValue;
    } //Function ends

} //Trait ends
