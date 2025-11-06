<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
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
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;


trait AuthenticatesUsers
{

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
                    foreach ($groups as $key => &$value) {
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
    protected function attemptLogin(Request|Collection $request,
        string $guard='web', string $paramUsername='email',
        string $paramPassword='password', bool $isJsonResponse=false,
        bool $isRaiseException=false)
    {
        try {
            $returnValue = null;
            $payload = null;

            //Convert request to collection
            if ($request instanceof Request) {
                $isJsonResponse = ($request->expectsJson() || $request->isJson());
                $payload = collect($request->all());
            } else {
                $payload = $request;
            } //End if

            //Get the password policy
            $passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($payload->only([$paramPassword])->toArray(), [
                $paramPassword => 'required|regex:'.$passwordPolicy['regex']
            ], [
                'regex' => 'Must contain atleast ' . $passwordPolicy['message']
            ]);
            if ($validator->fails()) {
                Log::error($validator->errors());
                throw new ValidationException($validator);
            } //End if

            //Authenticate User
            $returnValue = Auth::guard($guard)->attempt($payload->toArray(), false, $paramUsername, $paramPassword);
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLogin:Exception');
            if ($e instanceof ValidationException || ($isRaiseException)) {
                throw $e;
            } //End if

            if ($e instanceof CognitoIdentityProviderException) {
                $this->sendFailedCognitoResponse($e, $isJsonResponse, $paramUsername);
            }

            $returnValue = $this->sendFailedLoginResponse($payload, $e, $isJsonResponse, $paramUsername);
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    
    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Support\Collection  $request
     * @param  \string  $guard (optional)
     * @param  \bool  $isJsonResponse (optional)
     *
     * @return mixed
     */
    protected function attemptLoginMFA(Request|Collection $request,
        string $guard='web', bool $isJsonResponse=false,
        string $paramName='mfa_code')
    {
        try {
            // Initialize variables
            $response = null;
            $claim = null;

            //Validate request
            $validator = Validator::make($request->all(), $this->rulesMFA());

            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            if ($request instanceof Request) {
                //Validate request
                $validator = Validator::make($request->all(), $this->rulesMFA());

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                } //End if

                //Set the response type
                $isJsonResponse = ($request->expectsJson() || $request->isJson());

                $request = collect($request->all());
            } //End if

            //Generate challenge array
            $challenge = $request->only(['challenge_name', 'session', 'mfa_code'])->toArray();

            //Fetch user details
            switch ($guard) {
                case 'web': //Web
                    if (request()->session()->has($challenge['session'])) {
                        //Get stored session
                        $sessionToken = request()->session()->get($challenge['session']);
                        $username = $sessionToken['username'];
                        $challenge['username'] = $username;
                    } else{
                        throw new HttpException(400, 'ERROR_AWS_COGNITO_SESSION_MFA_CODE');
                    } //End if
                    break;
                
                case 'api': //API
                    $challengeData = Auth::guard($guard)->getChallengeData($challenge['session']);
                    $username = $challengeData['username'];
                    $challenge['username'] = $username;
                    break;
                
                default:
                    break;
            } //End switch

            //Authenticate User
            $claim = Auth::guard($guard)->attemptMFA($challenge);
        } catch (NoLocalUserException $e) {
            Log::error('AuthenticatesUsers:attemptLoginMFA:NoLocalUserException');
            $response = $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramUsername, $isRaiseException);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AuthenticatesUsers:attemptLoginMFA:CognitoIdentityProviderException');
            $response = $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramName, $isRaiseException);
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLoginMFA:Exception');
            if ($isJsonResponse) {
                throw $e;
            } else {
                Log::error($e);
                switch ($e->getMessage()) {
                    case 'ERROR_AWS_COGNITO_MFA_CODE_NOT_PROPER':
                        $paramName = 'mfa_code';
                        break;
                    
                    default:
                        $paramName = 'mfa_code';
                        break;
                } //Switch ends
                $response = $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramName, $isRaiseException);
            } //End if
        } //Try-catch ends

        return $claim?$claim:$response;
    } //Function ends


    /**
     * Handle Failed Cognito Exception
     *
     * @param CognitoIdentityProviderException $exception
     */
    private function sendFailedCognitoResponse(
        CognitoIdentityProviderException $exception,
        bool $isJsonResponse=false, string $paramName='email')
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
    private function sendFailedLoginResponse(
        Request|Collection $request, $exception=null,
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


    /**
     * Get the MFA authentication validation rules.
     *
     * @return array
     */
    protected function rulesMFA()
    {
        return [
            'challenge_name'    => 'required',
            'session'           => 'required',
            'mfa_code'          => 'required|numeric|min:4',
        ];
    } //Function ends

} //Trait ends
