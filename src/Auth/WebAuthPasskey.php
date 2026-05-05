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

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait WebAuthPasskey
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
     * Action to start registration of a passkey authenticator for the currently signed-in user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Request $request)
    {
        try {
            //Initialize parameters
            $returnValue = null;
            $guard = $this->resolveAuthenticatedGuard();

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get Authenticated user
            $authUser = Auth::guard($guard)->user();
            if (empty($authUser)) { throw new InvalidUserException(); }

            //Token Object
            $accessToken = Auth::guard($guard)->cognito()->getToken();
            if (empty($accessToken)) { throw new HttpException(400, 'EXCEPTION_INVALID_TOKEN'); }

            //Get the response from AWS Cognito for starting passkey registration
            $response = $client->startWebAuthnRegistration($accessToken);
            $returnValue = $this->response->success($response);

            return $returnValue;
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:start:Exception');
            Log::error($e);
            throw $e;
        }
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
            //Validate payload
            $validator = Validator::make($request->all(), [
                'credential' => ['required']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Initialize parameters
            $returnValue = null;
            $guard = $this->resolveAuthenticatedGuard();

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get Authenticated user
            $authUser = Auth::guard($guard)->user();
            if (empty($authUser)) { throw new InvalidUserException(); }

            //Token Object
            $accessToken = Auth::guard($guard)->cognito()->getToken();
            if (empty($accessToken)) { throw new HttpException(400, 'EXCEPTION_INVALID_TOKEN'); }

            //Get the response from AWS Cognito for completing passkey registration
            $response = $client->completeWebAuthnRegistration(
                $accessToken,
                json_decode($request['credential'], true)
            );

            return $this->response->success($response);
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:complete:Exception');
            Log::error($e);
            throw $e;
        }
    } //Function ends

    /**
     * Action to authenticate by responding to the passkey authentication challenge
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function challenge(Request $request, ?string $challengeName = null)
    {
        try {
            if (!empty($challengeName)) {
                $request->merge(['challenge_name' => $challengeName]);
            } //End if

            // If username present in query parameters is email, encode it before validation and processing
            if ($request->query('username')) {
                $email = urlencode($request->input('username'));
    
                //find %40 and replace with @ to avoid validation error
                $email = str_replace('%40', '@', $email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $request->merge(['username' => $email]);
                } //End if
            } //End if

            //Validate payload
            $validator = Validator::make($request->all(), [
                'username' => ['required'],
                'challenge_name' => ['sometimes', 'in:WEB_AUTHN,EMAIL_OTP,SMS_OTP']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Initialize parameters
            $returnValue = null;

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get the response from AWS Cognito for authenticating with passkey credentials
            $response = $client->authWebAuthnCredential(
                CognitoAuthFlowTypes::USER_AUTH,
                $request['username'],
                $request['challenge_name'] ?? null
            );

            return $this->response->success($response);
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:challenge:Exception');
            Log::error($e);
            throw $e;
        }
    } //Function ends

    /**
     * Authenticate by responding to the passkey authentication challenge
     *
     * @param string $authFlow The authentication flow to use. Must be either "USER_AUTH" or "CUSTOM_AUTH".
     * @param string $username The username of the user to authenticate.
     * @param string $challengeName The type of challenge to request. Must be either "WEB_AUTHN", "EMAIL_OTP", or "SMS_OTP".
     * @return \Aws\Result
     */
    public function authWebAuthnCredential(Request $request, string $guard='web')
    {
        try {
            return $this->response->success($response);
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:challenge:Exception');
            Log::error($e);
            throw $e;
        }
    } //Function ends

    /**
     * Resolve the authenticated guard from available contexts.
     *
     * @return string
     */
    private function resolveAuthenticatedGuard(): string
    {
        if (!empty(Auth::guard('web')->user())) {
            return 'web';
        } //End if

        if (!empty(Auth::guard('api')->user())) {
            return 'api';
        } //End if

        throw new InvalidUserException();
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
