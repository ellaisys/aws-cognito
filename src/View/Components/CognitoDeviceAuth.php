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

class CognitoDeviceAuth extends Component
{
    public string $userkeyB64encoded;
    public string $newDeviceData;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string|null $urlDeviceConfirmEndpoint = null,
        public string|null $urlDeviceDeleteEndpoint = null,
        public string $challengeNameValue = 'NONE',
        public string $secureCode = 'cognito-challenge-',
        public bool $includeGMP = true,
        public bool $includeCryptoJS = true,
        public bool $includeCryptoUtils = true,
    )
    {
        try {
            if ($urlDeviceConfirmEndpoint === null && (Route::has('cognito.action.user.device.create'))){
                $this->urlDeviceConfirmEndpoint = route('cognito.action.user.device.create');
            }

            if ($urlDeviceDeleteEndpoint === null && (Route::has('cognito.action.user.device.delete'))){
                $this->urlDeviceDeleteEndpoint = route('cognito.action.user.device.delete');
            }

            if (!$this->urlDeviceConfirmEndpoint || !$this->urlDeviceDeleteEndpoint) {
                throw new HttpException(400, 'Device endpoint URLs could not be found. Please ensure the routes are defined and named correctly.');
            }

            // Generate a base64-encoded user key
            $this->userkeyB64encoded = base64_encode($this->getUsername());

            // Get the new device data from the session if available
            $this->newDeviceData = $this->processNewDeviceData();
        } catch (Exception $e) {
            throw new HttpException(400, $e->getMessage());

        }
    } //Function end

    /**
     * Process new device data from the session.
     *
     * @return string
     */
    private function processNewDeviceData(): string
    {
        // Check authenticated claim data for new device metadata
        $claim = session() ? session()->get('claim') : null;
        $claimData = $claim ? $claim['data'] : null;
        $newDeviceData = ($claimData && isset($claimData['NewDeviceMetadata'])) ? $claimData['NewDeviceMetadata'] : null;

        if ($newDeviceData) {
            $newDeviceData = [
                'd-key' => $newDeviceData['DeviceKey'] ?? null,
                'd-grp' => $newDeviceData['DeviceGroupKey'] ?? null,
            ];
        } else {
            return '';
        }

        return base64_encode(json_encode($newDeviceData));
    } //Function end

    /**
     * Get the username from session or request data
     *
     * @return string
     */
    private function getUsername(): string
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
        return view('cognito::components.device.main');
    } //Function end
} //Class end
