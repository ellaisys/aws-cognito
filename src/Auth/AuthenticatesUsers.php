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
    protected function attemptLogin(Collection $request, string $guard='web', string $paramUsername='email', string $paramPassword='password', bool $isJsonResponse=false)
    {
        try {
            //Get the password policy
            $passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($request->only([$paramPassword])->toArray(), [
                $paramPassword => 'required|regex:'.$passwordPolicy['regex']
            ], [
                'regex' => 'Must contain atleast ' . $passwordPolicy['message']
            ]);
            if ($validator->fails()) {
                Log::info($validator->errors());
                throw new ValidationException($validator);
            } //End if

            //Get the configuration fields
            $userFields = config('cognito.cognito_user_fields');

            //Get key fields
            $keyUsername = $userFields['email'];
            $keyPassword = 'password';
            $rememberMe = $request->has('remember')?$request['remember']:false;

            //Generate credentials array
            $credentials = [
                $keyUsername => $request[$paramUsername],
                $keyPassword => $request[$paramPassword]
            ];

            //Authenticate User
            $claim = Auth::guard($guard)->attempt($credentials, $rememberMe);

        } catch (NoLocalUserException $e) {
            Log::error('AuthenticatesUsers:attemptLogin:NoLocalUserException');
            $user = $this->createLocalUser($credentials, $keyPassword);
            if ($user) {
                return $user;
            } //End if

            return $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramUsername);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AuthenticatesUsers:attemptLogin:CognitoIdentityProviderException');
            return $this->sendFailedCognitoResponse($e, $isJsonResponse, $paramUsername);
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLogin:Exception');
            return $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramUsername);
        } //Try-catch ends

        return $claim;
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
    protected function attemptLoginMFA($request, string $guard='web', bool $isJsonResponse=false, string $paramName='mfa_code')
    {
        try {
            if ($request instanceof Request) {
                //Validate request
                $validator = Validator::make($request->all(), $this->rulesMFA());

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                } //End if

                $request = collect($request->all());
            } //End if

            //Generate challenge array
            $challenge = $request->only(['challenge_name', 'session', 'mfa_code'])->toArray();

            //Fetch user details
            $user = null;
            switch ($guard) {
                case 'web': //Web
                    if (request()->session()->has($challenge['session'])) {
                        //Get stored session
                        $sessionToken = request()->session()->get($challenge['session']);
                        $username = $sessionToken['username'];
                        $challenge['username'] = $username;
                        $user = unserialize($sessionToken['user']);
                    } else{
                        throw new HttpException(400, 'ERROR_AWS_COGNITO_SESSION_MFA_CODE');
                    } //End if
                    break;
                
                case 'api': //API
                    $challengeData = Auth::guard($guard)->getChallengeData($challenge['session']);
                    $username = $challengeData['username'];
                    $challenge['username'] = $username;
                    $user = unserialize($challengeData['user']);
                    break;
                
                default:
                    $user = null;
                    break;
            } //End switch

            //Authenticate User
            $claim = Auth::guard($guard)->attemptMFA($challenge, $user);
        } catch (NoLocalUserException $e) {
            Log::error('AuthenticatesUsers:attemptLoginMFA:NoLocalUserException');

            $response = $this->createLocalUser($user->toArray());
            if ($response) {
                return $response;
            } //End if

            return $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramUsername);
        } catch (CognitoIdentityProviderException $e) {
            Log::error('AuthenticatesUsers:attemptLoginMFA:CognitoIdentityProviderException');
            return $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramName);
            
        } catch (Exception $e) {
            Log::error('AuthenticatesUsers:attemptLoginMFA:Exception');
            Log::error($e);
            switch ($e->getMessage()) {
                case 'ERROR_AWS_COGNITO_MFA_CODE_NOT_PROPER':
                    $paramName = 'mfa_code';
                    break;
                
                default:
                    $paramName = 'mfa_code';
                    break;
            } //Switch ends
            return $this->sendFailedLoginResponse($request, $e, $isJsonResponse, $paramName);
        } //Try-catch ends

        return $claim;
    } //Function ends


    /**
     * Create a local user if one does not exist.
     *
     * @param  array  $credentials
     * @return mixed
     */
    protected function createLocalUser(array $dataUser, string $keyPassword='password')
    {
        $user = null;
        if (config('cognito.add_missing_local_user')) {
            //Get user model from configuration
            $userModel = config('cognito.sso_user_model');

            //Remove password from credentials if exists
            if (array_key_exists($keyPassword, $dataUser)) {
                unset($dataUser[$keyPassword]);
            } //End if
            
            //Create user
            $user = $userModel::create($dataUser);
        } //End if

        return $user;
    } //Function ends


    /**
     * Handle Failed Cognito Exception
     *
     * @param CognitoIdentityProviderException $exception
     */
    private function sendFailedCognitoResponse(CognitoIdentityProviderException $exception, bool $isJsonResponse=false, string $paramName='email')
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
    private function sendFailedLoginResponse($request, $exception=null, bool $isJsonResponse=false, string $paramName='email')
    {
        $errorCode = 'cognito.validation.auth.failed';
        $message = 'FailedLoginResponse';
        if (!empty($exception)) {
            if ($exception instanceof CognitoIdentityProviderException) {
                $errorCode = $exception->getAwsErrorCode();
                $message = $exception->getAwsErrorMessage();
            } elseif ($exception instanceof ValidationException) {
                throw $exception;
            } else {
                $message = $exception->getMessage();
            } //End if
        } //End if

        if ($isJsonResponse) {
            return  response()->json([
                'error' => $errorCode,
                'message' => $message
            ], 400);
        } else {
            return redirect()
                ->back()
                ->withErrors([
                    $paramName => $message,
                ]);
        } //End if

        throw new HttpException(400, $message);
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
