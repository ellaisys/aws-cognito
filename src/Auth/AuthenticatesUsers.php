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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoUserPool;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Ellaisys\Cognito\Services\AwsCognitoJwksService;
use Ellaisys\Cognito\Services\AwsCognitoSrpService;

use Ellaisys\Cognito\Events\Auth\PreLogoutEvent;
use Ellaisys\Cognito\Events\Auth\PostLogoutEvent;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait AuthenticatesUsers
{
    use BaseAuthTrait;

    /**
     * Pulls list of groups attached to a user in Cognito
     *
     * @param string $username
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function getAdminListGroupsForUser(string $username)
    {
        $groups = null;

        try {
            $result = app()->make(AwsCognitoClient::class)->adminListGroupsForUser($username);

            if (!empty($result)) {
                $groups = $result['Groups'];

                if ((!empty($groups)) && is_array($groups)) {
                    foreach ($groups as &$value) {
                        unset($value['UserPoolId']);
                        unset($value['RoleArn']);
                    } //Loop ends
                } //End if
            } //End if
        } catch(Exception $e) {
            Log::error('AuthenticatesUsers:getAdminListGroupsForUser:Exception');
        } //Try-catch ends

        return $groups;
    } //End if

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \CognitoAuthFlowTypes  $authFlow (optional)
     * @param  \string  $paramUsername (optional)
     * @param  \string  $paramPassword (optional)
     * @param  \string  $deviceKeyParam (optional)
     *
     * @return mixed
     */
    protected function attemptLogin(Request $request,
        CognitoAuthFlowTypes $authFlow = CognitoAuthFlowTypes::USER_PASSWORD_AUTH,
        string $paramUsername='email',
        string $paramPassword='password',
        string $deviceKeyParam = 'device_key')
    {
        try {
            // Initialize variables
            $returnValue = null;
            $guard = $this->getGuard($request);

            //Get the password policy
            $passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make(
                $request->only([
                    $paramUsername,
                    $paramPassword,
                    $deviceKeyParam
                ]),
                [
                    $paramUsername => 'required|string',
                    $paramPassword => 'required|regex:' . $passwordPolicy['regex'],
                    $deviceKeyParam => 'sometimes|string'
                ], [
                    'regex' => 'Must contain atleast ' . $passwordPolicy['message']
                ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Authenticate User
            $response = Auth::guard($guard)->attempt(
                    $request->all(), false,
                    $paramUsername, $paramPassword,
                    $authFlow
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
            Log::error('AuthenticatesUsers:attemptLogin:Exception');
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Attempt to log the user into the application using SRP authentication flow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \CognitoAuthFlowTypes  $authFlow (optional)
     * @param  \string  $paramUsername (optional)
     * @param  \string  $paramPassword (optional)
     * @param  \string  $sessionTokenParam (optional)
     * @param  \string  $deviceKeyParam (optional)
     *
     * @return mixed
     */
    protected function attemptLoginSRP(Request $request,
        CognitoAuthFlowTypes $authFlow = CognitoAuthFlowTypes::USER_SRP_AUTH,
        string $paramUsername = 'email',
        string $paramPassword = 'password',
        string $sessionTokenParam = 'session_token',
        string $deviceKeyParam = 'device_key')
    {
        try {
            // Initialize variables
            $returnValue = null;
            $guard = $this->getGuard($request);

            //Generate the SRP_A parameter if not present in the request
            if (!$request->has($paramPassword)) {
                // Generate public and private ephemeral values
                $srpService = app()->make(AwsCognitoSrpService::class);
                $ephemeral = $srpService->generateEphemeral();

                //Request password authentication using SRP flow
                $request->merge([
                    $paramPassword => $ephemeral['public_key'], // SRP_A value
                    $sessionTokenParam => $ephemeral['session_token']
                ]);
            } //End if

            //Validate request
            $validator = Validator::make(
                $request->only([$paramUsername, $paramPassword, $sessionTokenParam, $deviceKeyParam]),
                [
                    $paramUsername  => 'required|string',
                    $paramPassword  => 'required|string',
                    $sessionTokenParam => 'required|string',
                    $deviceKeyParam    => 'sometimes|string'
                ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Authenticate User
            $returnValue = Auth::guard($guard)->attempt(
                    $request->all(), false,
                    $paramUsername, $paramPassword,
                    $authFlow
                );
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLoginSRP:Exception');
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Logout action for the API based approach.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function logout(Request $request, bool $forced = false)
    {
        $returnValue = null;
        try {
            //Initialize parameters
            $guard = $this->getGuard($request);

            //Raise Pre Logout Event
            event(new PreLogoutEvent(
                    $request->toArray(),
                    $request->ip()
                ));

            //Logout user
            Auth::guard($guard)->logout($forced);
            $response = ['message' => 'Successfully logged out'];

            //Raise Post Logout Event
            event(new PostLogoutEvent(
                    $request->toArray(),
                    $request->ip()
                ));

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $request->session()->invalidate();
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'success')
                    ->with('message', trans('cognito::messages.auth.logout_success'))
                    ->with('data', $response);
            } //Return response
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:logout:Exception');
            throw $e;
        } //End try-catch
        return $returnValue;
    } //Function ends

    /**
     * Authenticate by responding to the authentication challenge
     * @param Request $request
     *
     * @return mixed
     */
    protected function challenge(Request $request): mixed
    {
        $returnValue = null;
        try {
            // Initialize variables
            $guard = $this->getGuard($request);

            //Convert challenge name to upper case if present in the request
            if ($request->has('challenge_name')) {
                $request->merge(['challenge_name' => strtoupper($request['challenge_name'])]);
            } //End if

            //Build the challenge data based on the challenge type
            $request = $this->buildChallengeRequestData($request);

            //Validate payload
            $validator = Validator::make($request->all(), $this->rulesChallenge());
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Generate challenge array
            $challenge = $request->only([
                'challenge_name',
                'session', 'challenge_value']);

            $username = $this->getUsernameFromChallengeSession($request, $challenge['session'], $guard);
            if (empty($username)) {
                throw new InvalidUserException();
            } else {
                $challenge['username'] = $username;
            } //End if

            //Authenticate User
            $response = Auth::guard($guard)->attemptChallengeAuth($challenge);

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
            Log::error('AuthenticatesUsers:challenge:Exception');
            throw $e;
        }
        return $returnValue;
    } //Function ends

    /**
     * Get the username from the session or challenge data based on the guard type
     *
     * @param Request $request
     * @param string $session The session key to look for in the request session or challenge data
     * @param string $guard The authentication guard type (e.g., 'web' or 'api')
     *
     * @return string|null The username if found, or null if not found
     */
    private function getUsernameFromChallengeSession(Request $request,
        string $session, string $guard): ?string
    {
        $username = null;

        try {
            if(!empty($request['username'])) {
                $username = $request['username'];
            } else{
                //Fetch user details
                switch ($guard) {
                    case 'web': //Web
                        if ($request->session()->has($session)) {
                            //Get stored session
                            $sessionToken = $request->session()->get($session);
                            $username = $sessionToken['username'];
                        } else{
                            throw new InvalidUserException();
                        } //End if
                        break;
                    
                    case 'api': //API
                        $challengeData = Auth::guard($guard)->getChallengeData($session);
                        if (empty($challengeData) || empty($challengeData['username'])) {
                            throw new InvalidUserException();
                        } //End if

                        $username = $challengeData['username'];
                        break;
                    
                    default:
                        break;
                } //End switch
            } //End if
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:getUsernameFromChallengeSession:Exception');
            throw $e;
        } //Try-catch ends

        return $username;
    } //Function ends

    /**
     * Build the challenge request data based on the challenge type
     *
     * @param Request $request
     * @return Request
     * @throws ValidationException
     */
    private function buildChallengeRequestData(Request &$request): Request
    {
        try {
            if ($request->has('challenge_name')) {
                $challangeName = CognitoChallengeTypes::from($request['challenge_name']);
                if ($challangeName == CognitoChallengeTypes::SELECT_CHALLENGE &&
                    $request->has('challenge_value') &&
                    $request['challenge_value'] == 'PASSWORD_SRP') {
                    $request = $this->buildChallengeRequestDataForSRP($request);
                } elseif ($challangeName == CognitoChallengeTypes::PASSWORD_SRP) {
                    $request = $this->buildChallengeRequestDataForSRP($request);
                } elseif ($challangeName == CognitoChallengeTypes::PASSWORD_VERIFIER) {
                    $request = $this->buildChallengeRequestDataForPasswordVerifier($request, false);
                } elseif ($challangeName == CognitoChallengeTypes::DEVICE_PASSWORD_VERIFIER) {
                    $request = $this->buildChallengeRequestDataForPasswordVerifier($request, true);
                } else{
                    // Do Nothing
                }
            } //End if
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:buildChallengeRequestData:Exception');
            throw $e;
        } //Try-catch ends

        return $request;
    } //Function ends

    /**
     * Build the challenge request data for SRP authentication flow when
     * the challenge name is PASSWORD_SRP
     *
     * @param Request $request
     * @return Request
     * @throws ValidationException
     */
    private function buildChallengeRequestDataForSRP(Request &$request): Request
    {
        try {
            // Get the SRP parameters and generate A and a
            $srpService = app()->make(AwsCognitoSrpService::class);

            // Generate the SRP parameters and get the challenge response parameters
            $ephemeral = $srpService->generateEphemeral($request['session'] ?? null);

            // Append to existing challenge value if present as PASSWORD_SRP
            $challengeValue = null;
            if ($request->has('challenge_value') && $request['challenge_value'] == 'PASSWORD_SRP') {
                $challengeValue = json_encode([
                    'ANSWER' => $request['challenge_value'],
                    'SRP_A' => $ephemeral['public_key']
                ]);
            } else {
                $challengeValue = $ephemeral['public_key']; // SRP_A value
            } //End if

            // Add challenge response parameters to the request
            $request->merge([
                'session' => $ephemeral['session_token'],
                'challenge_value' => $challengeValue
            ]);
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:buildChallengeRequestDataForSRP:Exception');
            throw $e;
        } //Try-catch ends

        return $request;
    } //Function ends

    /**
     * Build the challenge request data for SRP authentication flow when
     * the challenge name is PASSWORD_VERIFIER or DEVICE_PASSWORD_VERIFIER
     *
     * @param Request $request
     * @param bool $isDeviceAuth (optional)
     * @return Request
     * @throws ValidationException
     */
    private function buildChallengeRequestDataForPasswordVerifier(Request &$request,
        bool $isDeviceAuth = false): Request
    {
        try {
            //Validate challenge payload
            $payload = json_decode($request['challenge_value'], true);
            if ($payload === null) {
                throw ValidationException::withMessages([
                        'challenge_value' => 'Missing or invalid challenge value'
                    ]);
            } //End if
            $validator = Validator::make(
                    $payload,
                    $this->rulesChallengeValue($isDeviceAuth)
                );
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Get the SRP parameters and generate A and a
            $srpService = app()->make(AwsCognitoSrpService::class);

            /**
             * Set the device authentication flag in the SRP service to
             * indicate if this is a device authentication challenge
             */
            $srpService->setIsDeviceAuth($isDeviceAuth);

            // Process the challenge and get the response parameters
            $challengeValue = $srpService->processChallenge(
                    $request['challenge_value'],
                    $request['session'],
                    $request['challenge_params'] ?? null
                );

            //Add challenge response parameters to the request
            $request->merge([
                'challenge_value' => json_encode($challengeValue)
            ]);
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:buildChallengeRequestDataForPasswordVerifier:Exception');
            throw $e;
        } //Try-catch ends

        return $request;
    } //Function ends

    /**
     * Get the challenge validation rules.
     *
     * @return array
     */
    protected function rulesChallenge()
    {
        return [
            'username'          => 'sometimes',
            'session'           => 'required',
            'challenge_name'    => 'required|string|in:SELECT_CHALLENGE,WEB_AUTHN,EMAIL_OTP,SMS_OTP,SOFTWARE_TOKEN_MFA,SMS_MFA,EMAIL_MFA,PASSWORD,PASSWORD_SRP,PASSWORD_VERIFIER,DEVICE_SRP_AUTH,DEVICE_PASSWORD_VERIFIER',
            'challenge_value'   => 'required|string',
            'challenge_params'  => 'sometimes|string'
        ];
    } //Function ends

    /**
     * Get the challenge validation rules.
     *
     * @return array
     */
    protected function rulesChallengeValue(bool $isDeviceAuth = false)
    {
        $rules = [
            'PASSWORD_CLAIM_SIGNATURE'      => 'sometimes|string',
            'PASSWORD_CLAIM_SECRET_BLOCK'   => 'required|string',
            'TIMESTAMP'                     => 'required|string',

            'PASSKEY_HASH'                  => 'required_without:PASSWORD_CLAIM_SIGNATURE|string',
            'MESSAGE_BASE64'                => 'sometimes|string',
        ];

        // Add device authentication specific rules
        if ($isDeviceAuth) {
            $rules['DEVICE_KEY'] = 'required|string';
            $rules['DEVICE_GROUP_KEY'] = 'required_with:PASSKEY_HASH|string';
        } //End if

        return $rules;
    } //Function ends

} //Trait ends
