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
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait BaseAuthTrait
{
    /**
     * Variable to indicate if the action
     * is called from controller
     */
    public bool $isControllerAction = false;

    /**
     * Variable to indicate if the response
     * is to be in json format
     */
    public bool $isJsonResponse = false;

    /**
     * Variable to indicate if the response
     * is to be raised as an exception
     */
    public bool $isRaiseException = false;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    public $redirectTo = null;

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

    /**
     * Method to check if the response is to be in json format
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function getIsJsonResponse(Request $request): bool
    {
        if ($this->isJsonResponse) {
            return true;
        } //End if

        if(!$this->isJsonResponse && ($request->expectsJson() || $request->isJson())) {
            $this->isJsonResponse = true;
        } //End if

        return $this->isJsonResponse;
    } //Function ends

    /**
     * The method to get the guard to be used for authentication based on the request type
     *
     * @return bool
     */
    protected function getGuard(Request $request): string
    {
        $guard = 'web';

        if($this->getIsJsonResponse($request)) {
            $guard = 'api';
        }

        return $guard;
    } //Function ends

    /**
     * Method to get the authenticated user based on the request type
     *
     * @param Request $request
     *
     * @return mixed
     * @throws InvalidUserException
     */
    protected function getAuthenticatedUser(Request $request)
    {
        try {
            // Determine the guard based on the request type
            $guard = $this->getGuard($request);

            // Get the authenticated user
            $authUser = Auth::guard($guard)->user();
            if (empty($authUser)) { throw new InvalidUserException(); }
            return $authUser;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getAuthenticatedUser:Exception');
            throw $e;
        }
    } //Function ends

    /**
     * Method to get the access token of the authenticated user based on the request type
     *
     * @param Request $request
     *
     * @return string
     * @throws HttpException
     */
    protected function getAccessToken(Request $request): string
    {
        try {
            // Determine the guard based on the request type
            $guard = $this->getGuard($request);

            // Get the access token for the authenticated user
            $accessToken = Auth::guard($guard)->cognito()->getToken();
            if (empty($accessToken)) { throw new HttpException(400, 'EXCEPTION_INVALID_TOKEN'); }
            return $accessToken;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getAccessToken:Exception');
            throw $e;
        }
    } //Function ends

    protected function getEmailFromQuery(Request $request, string $paramEmailName='email'): string|null
    {
        try {
            $email = null;

             // If email is present in query parameters, encode it before validation and processing
            if ($request->query($paramEmailName)) {
                $email = urlencode($request->input($paramEmailName));
                // Find %40 and replace with @ to avoid validation error
                $email = str_replace('%40', '@', $email);
            } //End if

            return $email;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getEmailFromQuery:Exception');
            return null;
        }
    } //Function ends

} //End trait
