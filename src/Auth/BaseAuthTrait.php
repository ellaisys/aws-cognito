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

use Aws\Result as AwsResult;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Application;

use Ellaisys\Cognito\AwsCognitoClient;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;


enum EncryptionTypes: string
{
    case DEFAULT = 'DEFAULT';
    case NONE = 'NONE';
    case URL_ENCODE = 'URL_ENCODE';
    case BASE64_ENCODE = 'BASE64_ENCODE';
    case RAW_URL_ENCODE = 'RAW_URL_ENCODE';
}

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
     * @var string|null
     */
    public string|null $redirectTo = null;

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
     * @param \Request $request
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
     * Get the redirect path.
     *
     * @return string
     */
    protected function redirectPath(?string $redirect=null): string
    {
        try {
            $returnValue = null;

            //Check if property exists and not null
            if (property_exists($this, 'redirectTo') && !is_null($this->redirectTo)) {
                $returnValue = $this->redirectTo;
            } elseif (!is_null($redirect)) {
                $returnValue = $redirect;
            } else {
                $returnValue = config('cognito.routes.web.login_page', 'cognito.form.login');
            } //End if

            if(empty($returnValue)) {
                throw new HttpException(400, 'Invalid redirect path value.');
            } //End if
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:redirectPath:Exception');
            $returnValue = config('cognito.routes.web.login_page', 'cognito.form.login');
        }
        return $returnValue;
    } //Function ends

    /**
     * Set the redirect path
     *
     * @param string $redirectTo
     */
    protected function setRedirectPath(string $redirectTo): void
    {
        $this->redirectTo = $redirectTo;
    } //Function ends

    /**
     * The method to get the guard to be used for authentication based on the request type
     *
     * @param \Request $request
     *
     * @return string
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
     * @param \Request $request
     *
     * @return mixed
     * @throws \InvalidUserException
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
     * Method to get the local provider model
     *
     * @return string
     */
    protected function getLocalProviderModel()
    {
        return Auth::getProvider()->getModel();
    } //Function ends

    /**
     * Method to get the access token of the authenticated user based on the request type
     *
     * @param \Request $request
     *
     * @return string
     * @throws \HttpException
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
        } //Try-catch ends
    } //Function ends

    /**
     * Method to get the claim of the authenticated user based on the request type
     *
     * @param \Request $request
     *
     * @return array
     * @throws \HttpException
     */
    protected function getClaim(Request $request): array
    {
        try {
            // Determine the guard based on the request type
            $guard = $this->getGuard($request);

            // Get the claim for the authenticated user
            $claim = Auth::guard($guard)->getClaim();
            if (empty($claim)) { throw new HttpException(400, 'EXCEPTION_INVALID_CLAIM'); }
            return $claim;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getClaim:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Get the data from the query parameter based on the parameter name and encryption type
     *
     * @param \Request $request
     * @param string $paramName (optional)
     * @param \EncryptionTypes $encryptionType (optional)
     * @param bool $filterEmail (optional)
     *
     * @return string|null
     * @throws \Exception
     */
    protected function getDataFromQueryParam(Request $request,
        string $paramName='email',
        EncryptionTypes $encryptionType=EncryptionTypes::DEFAULT,
        bool $filterEmail=false): string|null
    {
        try {
            $returnValue = null;

            // If email is present in query parameters, encode it before validation and processing
            if ($request->query($paramName)) {
                $data = $request[$paramName];

                switch ($encryptionType) {
                    case EncryptionTypes::BASE64_ENCODE:
                        $returnValue = base64_decode($data);
                        break;

                    case EncryptionTypes::RAW_URL_ENCODE:
                        $returnValue = rawurldecode($data);
                        break;

                    case EncryptionTypes::DEFAULT:
                    case EncryptionTypes::URL_ENCODE:
                        $data = urlencode($data);
                        // Find %40 and replace with @ to avoid validation error
                        $returnValue = str_replace('%40', '@', $data);
                        break;

                    case EncryptionTypes::NONE:
                    default:
                        $returnValue = $data;
                } //End switch

                // If filter email flag is true, validate the email and
                // return value only if valid email, else return null
                if(!($filterEmail && !empty($returnValue)
                    && (filter_var($returnValue, FILTER_VALIDATE_EMAIL))))
                {
                    $returnValue = null;
                } //End if
            } else {
                $returnValue = null;
            } //End if

            return $returnValue;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getDataFromQueryParam:Exception');
            return null;
        } //Try-catch ends
    } //Function ends

    /**
     * Generate a random password compatible with the default AWS Cognito
     * password policy.
     *
     * Ensures the password contains at least:
     * - One uppercase letter
     * - One lowercase letter
     * - One digit
     * - One special character
     *
     * @param int $length The total password length. Minimum is 8.
     *
     * @return string
     */
    protected function generateRandomPassword(int $length = 12): string
    {
        $length = max($length, 8);

        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}<>?';

        // Ensure one character from each required set.
        $password = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $lower . $upper . $numbers . $symbols;

        while (count($password) < $length) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    } //Function ends

    /**
     * Get the authenticated user from AWS Cognito based on the access token
     *
     * @param \Request $request
     *
     * @return \AwsResult
     * @throws \InvalidUserException
     */
    protected function getCognitoUser(Request $request): AwsResult
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            // Get Access Token for the authenticated user
            $accessToken = $this->getAccessToken($request);

            // Get the user details from AWS Cognito using the access token
            $response = $client->getUser($accessToken);
            if (empty($response)) {
                throw new InvalidUserException('No authenticated user found.');
            }
            return $response;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getCognitoUser:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Get the authenticated user from AWS Cognito by the username (admin action)
     *
     * @param \Request $request
     * @param string $username
     *
     * @return \AwsResult
     * @throws \InvalidUserException
     */
    protected function getCognitoUserByAdmin(Request $request, string $username='username'): AwsResult
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            // Get the user details from AWS Cognito using the access token
            $response = $client->adminGetUser($request[$username]);
            if (empty($response)) {
                throw new InvalidUserException('No authenticated user found.');
            }
            return $response;
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:getCognitoUserByAdmin:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Validate if the provided Cognito auth flow is allowed based on the configuration
     *
     * @param \CognitoAuthFlowTypes $authFlow
     *
     * @throws \AwsCognitoException
     */
    protected function validateCognitoFlow(CognitoAuthFlowTypes $authFlow): void
    {
        try {
            $allowedFlows = config('cognito.allowed_flows');
            if (!is_array($allowedFlows) || !in_array('ALLOW_' . $authFlow->value, $allowedFlows)) {
                throw new AwsCognitoException(AwsCognitoException::COGNITO_CONFIG_INVALID);
            } //End if
        } catch (Exception $e) {
            Log::error('BaseAuthTrait:validateCognitoFlow:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

} //End trait
