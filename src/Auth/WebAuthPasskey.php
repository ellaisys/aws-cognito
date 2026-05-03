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
