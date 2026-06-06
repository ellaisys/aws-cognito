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

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CognitoChallenge extends Component
{   
    public array|null $challengeData;
    public string $csrfToken;

    private string $cognitoPoolName = '';
    public string $challengeNameValue = '';
    private string $sessionValue = '';
    private string $challengeParamsValue = '';
    private string $usernameValue = '';
    private string $challengeValuePlaceholder = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        array|null $challengeData = null,
        string $csrfToken,
        public string|null $challengeFormName = null
    )
    {
        try {
            if (is_null($challengeData)) {
                throw new HttpException(400, 'The data for the challenge component is required.');
            }
            $this->challengeData = $challengeData;
            $this->csrfToken = $csrfToken;
            
            $this->processData($challengeData);
        } catch (Exception $e) {
            throw $e;
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
            $this->challengeNameValue = isset($data['challenge_name']) ? strtoupper($data['challenge_name']) : '';
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
        return view('cognito::components.challenge.main', [
            'data' => $this->challengeData,
            'csrfToken' => $this->csrfToken,
            'cognitoPoolName' => $this->cognitoPoolName,
            'challengeNameValue' => $this->challengeNameValue,
            'sessionValue' => $this->sessionValue,
            'challengeParamsValue' => $this->challengeParamsValue,
            'usernameValue' => $this->usernameValue,
            'challengeValuePlaceholder' => $this->challengeValuePlaceholder
        ]);
    } //Function end
} //Class end
