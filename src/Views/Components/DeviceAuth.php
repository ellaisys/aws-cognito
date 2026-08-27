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

class DeviceAuth extends CognitoBaseComponent
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
        public string $secureCode = 'device-',
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
            $this->secureCode .= $this->userkeyB64encoded;

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

        // Prepare new device data for encoding
        if ($newDeviceData) {
            $newDeviceData = [
                'd-key' => $newDeviceData['DeviceKey'] ?? null,
                'd-grp' => $newDeviceData['DeviceGroupKey'] ?? null,
            ];
        } else {
            return '';
        } //End if

        return base64_encode(json_encode($newDeviceData));
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
