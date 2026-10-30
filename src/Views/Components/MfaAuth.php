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

use Illuminate\Support\Facades\Route;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MfaAuth extends CognitoBaseComponent
{
    public bool $isAdminRole = false;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string|null $urlMfaActivateEndpoint = null,
        public string|null $urlMfaDeactivateEndpoint = null,
        public string|null $urlMfaEnableEndpoint = null,
        public string|null $urlMfaDisableEndpoint = null,
    )
    {
        try {
            if ($urlMfaActivateEndpoint === null && (Route::has('cognito.form.user.mfa.activate'))){
                $this->urlMfaActivateEndpoint = route('cognito.form.user.mfa.activate');
            }

            if ($urlMfaDeactivateEndpoint === null && (Route::has('cognito.action.user.mfa.deactivate'))){
                $this->urlMfaDeactivateEndpoint = route('cognito.action.user.mfa.deactivate');
            }

            if ($urlMfaEnableEndpoint === null && (Route::has('cognito.action.mfa.enable'))){
                $this->urlMfaEnableEndpoint = route('cognito.action.mfa.enable');
            }

            if ($urlMfaDisableEndpoint === null && (Route::has('cognito.action.mfa.disable'))){
                $this->urlMfaDisableEndpoint = route('cognito.action.mfa.disable');
            }

            if (!$this->urlMfaActivateEndpoint || !$this->urlMfaDeactivateEndpoint || !$this->urlMfaEnableEndpoint || !$this->urlMfaDisableEndpoint) {
                throw new HttpException(400, 'MFA activate, deactivate, enable and disable endpoint URLs could not be found. Please ensure the routes are defined and named correctly.');
            }

            // Determine if the current user has an admin role
            $this->isAdminRole = $this->isUserAdmin();
            
        } catch (Exception $e) {
            throw new HttpException(400, 'Error generating MFA endpoint URLs');
        }
    } //Function end

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|Closure|string
     */
    public function render(): View|Closure|string
    {
        // Check if MFA setup is enabled
        if (config('cognito.mfa_setup') !== 'OFF') {
            return view('cognito::components.mfa.main');
        } // End if

        return '';
    } //Function end
} //Class end
