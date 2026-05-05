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
     * @param  \Illuminate\Support\Collection  $request
     * @param  \string  $guard (optional)
     * @param  \string  $paramUsername (optional)
     * @param  \string  $paramPassword (optional)
     * @param  \bool  $isJsonResponse (optional)
     *
     * @return mixed
     */
    protected function attemptLogin(Request $request,
        string $paramUsername='email',
        string $paramPassword='password')
    {
        try {
            // Initialize variables
            $returnValue = null;
            $guard = 'web';

            if(!$this->isJsonResponse && ($request->expectsJson() || $request->isJson())) {
                $this->isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Get the password policy
            $passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($request->only([$paramPassword]), [
                $paramPassword => 'required|regex:'.$passwordPolicy['regex']
            ], [
                'regex' => 'Must contain atleast ' . $passwordPolicy['message']
            ]);
            if ($validator->fails()) {
                Log::error($validator->errors());
                throw new ValidationException($validator);
            } //End if

            //Authenticate User
            $returnValue = Auth::guard($guard)->attempt(
                    $request->only([$paramUsername, $paramPassword]), false,
                    $paramUsername, $paramPassword
                );
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLogin:Exception');
            if ($e instanceof ValidationException || ($this->isRaiseException)) {
                throw $e;
            } //End if

            if ($e instanceof CognitoIdentityProviderException) {
                $this->sendFailedCognitoResponse($e, $paramUsername);
            }

            $returnValue = $this->sendFailedLoginResponse($e, $this->isJsonResponse, $paramUsername);
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Authenticate by responding to the authentication challenge
     * @param Request $request
     *
     * @return mixed
     */
    protected function attemptLoginChallenge(Request $request): mixed
    {
        try {
            // Initialize variables
            $returnValue = null;
            $guard = 'web';

            if(!$this->isJsonResponse && ($request->expectsJson() || $request->isJson())) {
                $this->isJsonResponse = true;
                $guard = 'api';
            } //End if

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
            $returnValue = Auth::guard($guard)->attemptChallengeAuth($challenge);
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLoginChallenge:Exception');
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
            Log::error('AuthenticatesUsers:getUserNameFromChallengeSession:Exception');
            throw $e;
        } //Try-catch ends

        return $username;
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
            'challenge_name'    => 'required|in:WEB_AUTHN,EMAIL_OTP,SMS_OTP,SOFTWARE_TOKEN_MFA,SMS_MFA,EMAIL_MFA',
            'challenge_value'   => 'required',
        ];
    } //Function ends

    /**
     * Handle Failed Cognito Exception
     *
     * @param CognitoIdentityProviderException $exception
     */
    private function sendFailedCognitoResponse(
        CognitoIdentityProviderException $exception,
        string $paramName='email')
    {
        throw ValidationException::withMessages([
            $paramName => $exception->getAwsErrorMessage(),
        ]);
    } //Function ends

    /**
     * Handle Generic Exception
     *
     * @param  \Collection $request
     * @param  \Exception $exception
     */
    private function sendFailedLoginResponse($exception,
        bool $isJsonResponse=false, string $paramName='email')
    {
        $errorCode = 400;
        $errorMessageCode = 'cognito.validation.auth.failed';
        $message = 'FailedLoginResponse';
        if (!empty($exception)) {
            if ($exception instanceof CognitoIdentityProviderException) {
                $errorMessageCode = $exception->getAwsErrorCode();
                $message = $exception->getAwsErrorMessage();
            } elseif ($exception instanceof ValidationException) {
                throw $exception;
            } else {
                $errorCode = $exception->getStatusCode();
                $message = $exception->getMessage();
            } //End if
        } //End if

        if ($isJsonResponse) {
            return response()->json([
                'error' => $errorMessageCode,
                'message' => $message
            ], $errorCode);
        } else {
            return redirect()
                ->back()
                ->withErrors([
                    'error' => $errorMessageCode,
                    $paramName => $message,
                ]);
        } //End if
    } //Function ends

} //Trait ends
