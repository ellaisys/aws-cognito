<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Views\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base Component for AWS Cognito
 */
class CognitoBaseComponent extends Component
{
    /**
     * Get the username from session or request data
     *
     * @return string
     */
    protected function getUsername(): string
    {
        $username = 'cognito-user';
        
        // Check session data for username
        $sessionUsername = $this->getSessionUsername();

        // Check request data for username
        $requestUsername = $this->getRequestUsername();

        if ($sessionUsername) {
            $username = $sessionUsername;
        } elseif ($requestUsername) {
            $username = $requestUsername;
        } else {
            $username = $this->getAuthenticatedUser() ?? $username;
        } // End if

        return $username;
    } //Function end

    /**
     * Get the username from the session data
     *
     * @return string|null
     */
    private function getSessionUsername(): ?string
    {
        try {
            // Initialize username variable
            $username = null;

            // Check authenticated claim data
            $claim = session() ? session()->get('claim') : null;

            // Check challenge data
            $challengeData = session('data') ?? null;

            if ($claim && isset($claim['username'])) {
                $username = $claim['username'];
            } elseif ($challengeData && isset($challengeData['status'])
                && $challengeData['status'] == 'challenge') {
                
                // If the challenge data contains a username
                $challengeParamsValue = $challengeData['challenge_params'] ?? null;
                if ($challengeParamsValue && isset($challengeParamsValue['USER_ID_FOR_SRP'])) {
                    $username = $challengeParamsValue['USER_ID_FOR_SRP'];
                } elseif ($challengeParamsValue && isset($challengeParamsValue['USERNAME'])) {
                    $username = $challengeParamsValue['USERNAME'];
                } else {
                    $username = $challengeData['username'] ?? $username;
                } // End if
            } else {
                $username = null;
            } // End if
            return $username;
        } catch (Exception $exception) {
            Log::error('CognitoBaseComponent:getSessionUsername:Exception');
            throw $exception;
        } //End try-catch
    } //Function end

    /**
     * Get the username from the request data
     *
     * @return string|null
     */
    private function getRequestUsername(): ?string
    {
        try {
            // Initialize username variable
            $username = null;

            // Return query to request data for the user
            $query = $this->getRequestUserCredentials();
            if (isset($query[config('cognito.user_subject_uuid')])) {
                return $query[config('cognito.user_subject_uuid')];
            } elseif (isset($query['email'])) {
                return $query['email'];
            } else {
                $provider = Auth::createUserProvider('users');
                $user = $provider->retrieveByCredentials($query);
                return ($user && isset($user['sub'])) ? $user['sub'] : null;
            } // End if
        } catch (Exception $exception) {
            Log::error('CognitoBaseComponent:getRequestUsername:Exception');
            throw $exception;
        } //End try-catch
    } //Function end

    /**
     * Get the user credentials from the request data
     *
     * @return array
     */
    private function getRequestUserCredentials(): array
    {
        try {
            // Initialize username variable
            $returnValue = null;

            // Check request data for username or email
            if (request()->has('username')) {
                $returnValue = request()->get('username');
            } elseif (request()->has('email')) {
                $returnValue = request()->get('email');
            } else {
                $returnValue = null;
            } // End if

            // Return the appropriate array
            if (filter_var($returnValue, FILTER_VALIDATE_EMAIL)) {
                return ['email' => $returnValue];
            } else {
                return [config('cognito.user_subject_uuid') => $returnValue];
            } // End if
        } catch (Exception $exception) {
            Log::error('CognitoBaseComponent:getRequestUserCredentials:Exception');
            throw $exception;
        } //End try-catch
    } //Function end

    /**
     * Get the authenticated user
     *
     * @return string|null
     */
    private function getAuthenticatedUser(): ?string
    {
        try {
            if (auth()->user()) {
                return auth()->user()->email;
            } else {
                return null;
            }
        } catch (Exception $exception) {
            Log::error('CognitoBaseComponent:getAuthenticatedUser:Exception');
            throw $exception;
        } //End try-catch
    } //Function end

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('cognito::components.passkey.webauthn');
    } //Function end

} //Class end
