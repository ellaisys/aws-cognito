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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait RefreshToken
{
    use BaseAuthTrait;

    /**
     * Passed params
     */
    private $paramRefreshToken = 'refresh_token';
    private $paramUsername = 'email';
    private $deviceKey = 'device_key';

    /**
     * Generate a new token.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     *
     * @return mixed
     */
    public function refresh(Request $request, ?array $clientMetadata = null)
    {
        // Initialize variables
        $returnValue = null;

        try {
            //Initialize variables
            $refreshToken = $request->has($this->paramRefreshToken) ? $request[$this->paramRefreshToken] : null;
            $username = $request->has($this->paramUsername) ? $request[$this->paramUsername] : null;

            //Check if the refresh token and username are provided
            if (empty($refreshToken) && empty($username)) {
                //Get from authenticated user
                $authUser = $this->getAuthenticatedUser($request);
                $username = $username ?? $authUser[$this->paramUsername];

                //Get claim from guard
                $claim = $this->getClaim($request);
                $refreshToken = $refreshToken ?? $claim['data']['RefreshToken'] ?? null;
                
                //Merge the value into the request object
                $request->merge([
                    $this->paramUsername => $username,
                    $this->paramRefreshToken => $refreshToken,
                ]);
            } //End if

            //Process token refresh
            $returnValue = $this->revalidate(
                    $request,
                    $this->paramUsername, $this->paramRefreshToken,
                    $clientMetadata
                );
        } catch(Exception $exception) {
            Log::error('RefreshToken:refresh:Exception');
            throw $exception;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Generate a new token with the given request.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $paramRefreshToken (optional)
     *
     * @return mixed
     */
    public function revalidate(Request $request,
        string $paramUsername='email',
        string $paramRefreshToken='refresh_token',
        ?array $clientMetadata = null)
    {
        // Initialize variables
        $returnValue = null;

        try {
            //Assign params
            $this->paramRefreshToken = $paramRefreshToken;
            $this->paramUsername = $paramUsername;

            // If username present in query parameters is email, decode it before validation and processing
            $email = $this->getDataFromQueryParam($request, $this->paramUsername, EncryptionTypes::URL_ENCODE, true);
            if (!empty($email)) {
                $request->merge([$this->paramUsername => $email]);
            } //End if
            
            //Validate request
            $this->validateRefreshRequest($request);

            //Process token refresh
            $response = $this->processTokenRefresh($request, $clientMetadata);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                // Regenerate the session to prevent session fixation attacks
                $request->session()->regenerate();

                // Set the redirect path to the home page defined in the configuration
                $this->redirectTo = config('cognito.routes.web.home_page');

                // Call response redirect with status, message, and data
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.auth.login_success'))
                    ->with('data', $response);
            } //Return response
        } catch(Exception $exception) {
            Log::error('RefreshToken:revalidate:Exception');
            throw $exception;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Validate the refresh token request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return void
     */
    private function validateRefreshRequest(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), $this->rules());

            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new HttpException(400, 'ERROR_VALIDATION');
        } //Try-catch ends
    } //Function ends

    /**
     * Process the token refresh.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    private function processTokenRefresh(Request $request, ?array $clientMetadata = null): mixed
    {
        //Initialize variables
        $returnValue = null;

        try {
            //Initialize variables
            $refreshToken = $request->has($this->paramRefreshToken) ? $request[$this->paramRefreshToken] : null;
            $username = $request->has($this->paramUsername) ? $request[$this->paramUsername] : null;
            $deviceKey = $request->has($this->deviceKey) ? $request[$this->deviceKey] : null;

            //Get guard
            $guard = $this->getGuard($request);

            //Get refresh token
            $response = Auth::guard($guard)->refreshToken(
                    $refreshToken, $username,
                    $deviceKey, $clientMetadata
                );

            //Return the response object
            $returnValue = $response->getData();
        } catch (Exception $exception) {
            Log::error('RefreshToken:processTokenRefresh:Exception');
            throw $exception;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            $this->paramRefreshToken => 'required|string',
            $this->paramUsername => 'required|email',
            $this->deviceKey => 'sometimes|string',
        ];
    } //Function ends

} //Trait ends
