<form id="auth-challenge-form" method="POST" action="{{ route('cognito.action.auth.challenge.submit') }}">

    <x-cognito-challenge 
        :challenge-form-name="'auth-challenge-form'" />
        
    {{-- @php
        $data = (session('data')) ?? null;
        if ($data && isset($data['status']) && $data['status'] == 'challenge') {
            $usernameValue = $data['username'] ?? null;
            $sessionValue = $data['session_token'] ?? null;
            $challengeNameValue = isset($data['challenge_name']) ? strtoupper($data['challenge_name']) : null;
            $challengeParamsValue = isset($data['challenge_params']) ? json_encode($data['challenge_params'], JSON_UNESCAPED_SLASHES) : '';

            if (in_array($challengeNameValue, ['EMAIL_OTP', 'SMS_OTP'])) {
                $challengeValueText = $data['challenge_params']['CODE_DELIVERY_DELIVERY_MEDIUM'] ?? '';
                $challengeValueText .= ' sent to ' . ($data['challenge_params']['CODE_DELIVERY_DESTINATION'] ?? '');
            }
        } else {
            $usernameValue = (request()->has('username'))? request()->get('username') : null;
            $sessionValue = (request()->has('session'))? request()->get('session') : null;
            $challengeNameValue = (request()->has('challenge'))? strtoupper(request()->get('challenge')) : null;
            $challengeParamsValue = '';
            $challengeValueText = '';
        }

        //PoolName without region prefix (e.g., "us-east-1_XXXXXXXXX:app/clientid" => "app/clientid")
        $namePool = config('cognito.user_pool_id');
        $namePool = strpos($namePool, '_') !== false ? explode('_', $namePool, 2)[1] : $namePool;
    @endphp --}}


    <div class="row mb-3">
        @stack('cognito-challenge-passcode')
    </div>

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            {{-- @if (!in_array($challengeNameValue, ['WEB_AUTHN', 'DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER']))
            <button type="submit" id="auth-challenge-form-submit-button" class="btn btn-primary"
                onclick="handleFormSubmit(event);">
                {{ __('Login') }}
            </button>
            @endif --}}

            <button type="submit" class="btn btn-primary" onclick="handleFormSubmit(event);">
                {{ __('Login') }}
            </button>

            @if (Route::has('cognito.form.register'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.register') }}">
                    {{ __('Register?') }}
                </a>
            @endif
        </div>
    </div>
</form>

@stack('cognito-challenge-scripts')
