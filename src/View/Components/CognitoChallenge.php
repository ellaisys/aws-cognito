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
use Illuminate\Support\Facades\Log;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CognitoChallenge extends Component
{
    private string $cognitoPoolName = '';
    private string $challengeNameValue = 'NONE';
    private string $sessionValue = '';
    private string $challengeParamsValue = '';
    private string $usernameValue = '';
    private string $challengeValuePlaceholder = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string|null $challengeFormName = null,
        public string $secureCode = 'cognito-challenge-',
    )
    {
        try {
            $challengeData = session('data') ?? null;
            if (!is_null($challengeData)) {
                // Process the data to extract necessary information for the view
                $this->processData($challengeData);
            }
        } catch (HttpException $exception) {
            Log::error('CognitoChallenge:constructor:HttpException');
            throw $exception;
        } catch (Exception $exception) {
            Log::error('CognitoChallenge:constructor:Exception');
            throw new HttpException(400, $exception->getMessage());
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
        return view('cognito::components.challenge.main', [
            'srpParameters' => config('cognito.srp_parameters'),
            'cognitoPoolName' => $this->cognitoPoolName,
            'challengeNameValue' => $this->challengeNameValue,
            'sessionValue' => $this->sessionValue,
            'challengeParamsValue' => $this->challengeParamsValue,
            'usernameValue' => $this->usernameValue,
            'challengeValuePlaceholder' => $this->challengeValuePlaceholder
        ]);
    } //Function end
} //Class end
