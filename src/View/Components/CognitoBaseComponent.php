<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

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
        
        // Check authenticated claim data
        $claim = session() ? session()->get('claim') : null;

        // Check challenge data
        $challengeData = session('data') ?? null;

        // Check request data for username
        $requestUsername = request()->has('username') ? request()->get('username') : null;

        if ($claim && isset($claim['email'])) {
            $username = $claim['email'];
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
            }
        } elseif ($requestUsername) {
                $username = $requestUsername;
        } else {
            $username = auth()->user() ? auth()->user()->email : $username;
        } // End if

        return $username;
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
