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
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait ImpersonateUsers
{
    /**
     * The AwsCognito instance.
     *
     * @var \Ellaisys\Cognito\AwsCognito
     */
    protected $cognito;


    /**
     * Constructor.
     *
     * @param AwsCognito $cognito
     */
    public function __construct(AwsCognito $cognito) {
        $this->cognito = $cognito;
    }

    
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
    protected function login(Collection $request, string $guard='web', string $paramUsername='email', string $paramPassword='password', bool $isJsonResponse=false)
    {
        try {
            //Get key fields
            $keyUsername = 'email';
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

            if (config('cognito.add_missing_local_user_sso')) {
                $response = $this->createLocalUser($credentials, $keyPassword);

                if ($response) {
                    return $response;
                } //End if
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
     * @param  \string  $paramUsername (optional)
     * @param  \string  $paramPassword (optional)
     * @param  \bool  $isJsonResponse (optional)
     *
     * @return mixed
     */
    protected function logout(Collection $request, string $guard='web', string $paramUsername='email', string $paramPassword='password', bool $isJsonResponse=false)
    {
        try {
            //Get key fields
            $keyUsername = 'email';
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

            if (config('cognito.add_missing_local_user_sso')) {
                $response = $this->createLocalUser($credentials, $keyPassword);

                if ($response) {
                    return $response;
                } //End if
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
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'refresh_token'    => 'required'
        ];
    } //Function ends

} //Trait ends
