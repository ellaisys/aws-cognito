<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Traits;

use Illuminate\Http\Request;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

use Illuminate\Foundation\Auth\AuthenticatesUsers as BaseAuthenticatesUsers;

trait AuthenticateUser
{
    use BaseAuthenticatesUsers;

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        try {
            $response = $this->guard()->attempt($this->credentials($request), $request->has('remember'));
        } catch (NoLocalUserException $e) {
            $response = $this->createLocalUser($this->credentials($request));
        }

        return $response;
    } //Function ends


    /**
     * Create a local user if one does not exist.
     *
     * @param  array  $credentials
     * @return mixed
     */
    protected function createLocalUser($credentials)
    {
        return true;
    } //Function ends


    /**
     * @param Request $request
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        try {
            if ($this->attemptLogin($request)) {
                return $this->sendLoginResponse($request);
            }
        } catch (CognitoIdentityProviderException $c) {
            return $this->sendFailedCognitoResponse($c);
        } catch (Exception $e) {
            return $this->sendFailedLoginResponse($request);
        }

        return $this->sendFailedLoginResponse($request);
    } //Function ends


    /**
     * @param CognitoIdentityProviderException $exception
     */
    private function sendFailedCognitoResponse(CognitoIdentityProviderException $exception)
    {
        throw ValidationException::withMessages([
            $this->username() => $exception->getAwsErrorMessage(),
        ]);
    } //Function ends

} //Trait ends