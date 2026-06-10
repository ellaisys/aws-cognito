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

class CognitoPasskeyWebAuthn extends Component
{   
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string|null $urlPasskeyStartEndpoint = null,
        public string|null $urlPasskeyCompleteEndpoint = null,
        public string|null $urlPasskeyDeleteEndpoint = null
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
        } catch (Exception $e) {
            throw new HttpException(400, 'Error generating passkey endpoint URLs');
        }
    }

    private function processData(array $data): void
    {
        // Get the pool name from the config file
        $namePool = config('cognito.user_pool_id');
        if (empty($namePool)) {
            throw new HttpException(400, 'The user pool ID is not set in the configuration.');
        }
        $this->cognitoPoolName = strpos($namePool, '_') !== false ? explode('_', $namePool, 2)[1] : $namePool;

        // Process the data
        if ($data && isset($data['status']) && $data['status'] == 'challenge') {
            $this->usernameValue = $data['username'] ?? '';
            $this->sessionValue = $data['session_token'] ?? '';
            $this->challengeNameValue = isset($data['challenge_name']) ? strtoupper($data['challenge_name']) : 'NONE';
            $this->challengeParamsValue = isset($data['challenge_params']) ? json_encode($data['challenge_params'], JSON_UNESCAPED_SLASHES) : '';

            if (in_array($this->challengeNameValue, ['EMAIL_OTP', 'SMS_OTP'])) {
                $this->challengeValuePlaceholder = $data['challenge_params']['CODE_DELIVERY_DELIVERY_MEDIUM'] ?? '';
                $this->challengeValuePlaceholder .= ' sent to ' . ($data['challenge_params']['CODE_DELIVERY_DESTINATION'] ?? '');
            }
        } else {
            throw new HttpException(400, 'The data provided is not valid for a challenge response.');
        }
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
