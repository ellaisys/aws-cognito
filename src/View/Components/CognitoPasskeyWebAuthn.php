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

class CognitoPasskeyWebAuthn extends CognitoBaseComponent
{
    public bool $passkeyEnabled = false;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string|null $urlPasskeyStartEndpoint = null,
        public string|null $urlPasskeyCompleteEndpoint = null,
        public string|null $urlPasskeyDeleteEndpoint = null,
        public string $secureCode = 'webpasskey-'
    )
    {
        try {
            if ($urlPasskeyStartEndpoint === null && (Route::has('cognito.action.user.passkey.start'))){
                $this->urlPasskeyStartEndpoint = route('cognito.action.user.passkey.start');
            }

            if ($urlPasskeyCompleteEndpoint === null && (Route::has('cognito.action.user.passkey.complete'))){
                $this->urlPasskeyCompleteEndpoint = route('cognito.action.user.passkey.complete');
            }

            if ($urlPasskeyDeleteEndpoint === null && (Route::has('cognito.action.user.passkey.delete'))){
                $this->urlPasskeyDeleteEndpoint = route('cognito.action.user.passkey.delete');
            }

            if (!$this->urlPasskeyStartEndpoint || !$this->urlPasskeyCompleteEndpoint || !$this->urlPasskeyDeleteEndpoint) {
                throw new HttpException(400, 'Passkey endpoint URLs could not be found. Please ensure the routes are defined and named correctly.');
            }

            // Generate a base64-encoded user key
            $this->userkeyB64encoded = base64_encode($this->getUsername());
            $this->secureCode .= $this->userkeyB64encoded;

            // Check if WebAuthn is enabled for the authenticated user
            $this->passkeyEnabled = $this->isWebAuthEnabled();
            
        } catch (Exception $e) {
            throw new HttpException(400, 'Error generating passkey endpoint URLs');
        }
    } //Function end

    /**
     * Check if WebAuthn is enabled for the authenticated user.
     *
     * @return bool
     */
    private function isWebAuthEnabled(): bool
    {
        return (auth()->user() && isset(auth()->user()->is_webauthn_enabled)) ? (auth()->user()->is_webauthn_enabled) : false;
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
