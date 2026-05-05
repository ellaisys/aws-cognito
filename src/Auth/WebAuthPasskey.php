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
            $guard = 'web';

            if(!$this->isJsonResponse) {
                $this->isJsonResponse = ($request->expectsJson() || $request->isJson());
                $guard = 'api';
            } //End if

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
            // Initialize variables
            $returnValue = null;
            $guard = 'web';

            if(!$this->isJsonResponse) {
                $this->isJsonResponse = ($request->expectsJson() || $request->isJson());
                $guard = 'api';
            } //End if

            //Validate payload
            $validator = Validator::make($request->all(), [
                'credential' => ['required']
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

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

            $returnValue = $this->response->success($response);
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:complete:Exception');
            Log::error($e);
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
    public function challenge(Request $request, ?string $challengeName = null)
    {
        try {
            // Initialize variables
            $returnValue = null;
            $guard = 'web';

            if(!$this->isJsonResponse) {
                $this->isJsonResponse = ($request->expectsJson() || $request->isJson());
                $guard = 'api';
            } //End if

            if (!empty($challengeName)) {
                $request->merge(['challenge_name' => $challengeName]);
            } //End if

            // If username present in query parameters is email, encode it before validation and processing
            if ($request->query('username')) {
                $email = base64_decode($request['username']);
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

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get the response from AWS Cognito for authenticating with passkey credentials
            $response = $client->authWebAuthnCredential(
                CognitoAuthFlowTypes::USER_AUTH,
                $request['username'],
                $request['challenge_name'] ?? null
            );

            $returnValue = $this->response->success($response);
        } catch (Exception $e) {
            Log::error('WebAuthPasskeyController:challenge:Exception');
            Log::error($e);
            throw $e;
        }

        return $returnValue;
    } //Function ends

} //Trait ends
