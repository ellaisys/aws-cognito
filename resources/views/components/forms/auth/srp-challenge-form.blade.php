<form method="POST" id="auth-challenge-form" action="{{ route('cognito.action.auth.challenge.submit') }}">
    @csrf

    @php
        $usernameValue = (request()->has('username'))? request()->get('username') : null;
        $sessionValue = (request()->has('session'))? request()->get('session') : null;
        $challengeNameValue = (request()->has('challenge'))? request()->get('challenge') : null;
    @endphp

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input type="hidden" id="challenge_name" name="challenge_name" value="{{ $challengeNameValue }}" />
            <input type="hidden" id="session" name="session" value="{{ $sessionValue }}" />
            <input id="challenge_value" type="hidden" name="challenge_value" />
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror @if($usernameValue) is-valid @endif"
                name="username" value="{{ old('username', $usernameValue) }}"
                @if($usernameValue) readonly autocomplete="off" @else required autocomplete="email" autofocus @endif
                />
        </div>
    </div>

    <div class="row mb-3">
        <label for="password_code" class="col-md-4 col-form-label text-md-end" id="challenge_value_label">{{ __('Password') }}</label>

        <div class="col-md-6">
            <input id="password_code" type="password"
                class="form-control @error('password_code') is-invalid @enderror"
                name="password_code" required autocomplete="off" />

            @error('password_code')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" id="auth-srp-challenge-button" class="btn btn-primary"
                onclick="return submitChallengeForm()">
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

<script>
    const urlAuthSrpChallenge = "{{Route::has('cognito.action.auth.srp.challenge') ? (route('cognito.action.auth.srp.challenge')) : 'null'}}";
    const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';
    const namePoolId = "{{ config('cognito.user_pool_id') }}";

    window.addEventListener('load', (event) => {
        getChallengeData();
    });

    // Function to handle the SRP authentication process
    async function getChallengeData() {
        try {
            const challengeNameValue = document.getElementById('challenge_name');
            const challengeValue = document.getElementById('challenge_value');
            const sessionValue = document.getElementById('session');

            var response = await fetch(urlAuthSrpChallenge, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    email: "{{ $usernameValue }}"
                })
            });

            if (!response.ok) {
                throw new Error('Failed to get SRP authentication challenge');
            } //End if

            responseData = await response.json();
            responseData = responseData.data || responseData;

            //Set the session value received from the server
            sessionValue.value = responseData.session_token || '';
            challengeNameValue.value = responseData.challenge_name || '';

            // If the challenge is for WebAuthn, set the challenge value and submit the form
            if (responseData.challenge_name == 'PASSWORD_VERIFIER') {
                challengeValue.value = JSON.stringify(responseData.challenge_params || {});
            } // End if
        } catch (error) {
            console.error('Error authenticating SRP:', error);
        } // End try-catch
    }

    // Submit the challenge form and mask the password
    async function submitChallengeForm() {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password_code');
        const challengeValueInput = document.getElementById('challenge_value');

        // Get Name and remove region prefix if present (e.g., "us-east-1_XXXXXXXXX:app/clientid" => "app/clientid")
        const namePool = namePoolId.includes('_') ? namePoolId.split('_')[1] : namePoolId;
        
        // Set the actual password value in the hidden challenge value input
        let passKey = namePool + usernameInput.value + ':' + passwordInput.value;

        // Hash with SHA256 and set the hashed value in the challenge value input
        let passKeyHash = await window.crypto.subtle.digest("SHA-256", new TextEncoder().encode(passKey));

        // Convert the hash to a hex string
        passKeyHash = Array.from(new Uint8Array(passKeyHash)).map(b => b.toString(16).padStart(2, '0')).join('');

        let challengeValue = challengeValueInput.value ? JSON.parse(challengeValueInput.value) : {};
        challengeValue.PASSKEY_HASH = passKeyHash;
        challengeValueInput.value = JSON.stringify(challengeValue);// Add the hashed password to the challenge value input

        return true; // Allow the form to submit
    }
</script>
