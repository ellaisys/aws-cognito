<form method="POST" id="auth-challenge-form" action="{{ route('cognito.action.auth.challenge.submit') }}">
    @csrf

    @php
        $data = (session('data')) ?? null;
        if ($data && isset($data['status']) && $data['status'] == 'challenge') {
            $usernameValue = $data['username'] ?? null;
            $sessionValue = $data['session_token'] ?? null;
            $challengeNameValue = $data['challenge_name'] ?? null;
        } else {
            $usernameValue = (request()->has('username'))? request()->get('username') : null;
            $sessionValue = (request()->has('session'))? request()->get('session') : null;
            $challengeNameValue = (request()->has('challenge'))? request()->get('challenge') : null;
        }

        //PoolName without region prefix (e.g., "us-east-1_XXXXXXXXX:app/clientid" => "app/clientid")
        $namePool = config('cognito.user_pool_id');
        $namePool = strpos($namePool, '_') !== false ? explode('_', $namePool, 2)[1] : $namePool;
    @endphp

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input type="hidden" id="challenge_name" name="challenge_name" value="{{ $challengeNameValue }}" />
            <input type="hidden" id="session" name="session" value="{{ $sessionValue }}" />
            <input type="hidden" id="challenge_params" name="challenge_params"
                value="{{ ($data && isset($data['challenge_params'])) ? json_encode($data['challenge_params']) : '' }}" />
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
                onclick="submitChallengeForm();">
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
    const poolKey = "{{ base64_encode($namePool) }}";
    const usernameValue = "{{ $usernameValue }}";

    window.addEventListener('load', (event) => {
        if (usernameValue && (usernameValue.trim().length > 0)){
            getChallengeData();
        } // End if
    });

    /**
     * Function to fetch the SRP authentication challenge from the server.
     *
     * It sends the user's email to the server and expects to receive the
     * challenge name, challenge parameters, and session token in response.
     * The function then updates the form with the received challenge data,
     * allowing the user to proceed with the authentication process.
     **/
    async function getChallengeData() {
        try {
            const challengeNameValue = document.getElementById('challenge_name');
            const challengeValue = document.getElementById('challenge_value');
            const sessionValue = document.getElementById('session');

            let response = await fetch(urlAuthSrpChallenge, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    username: usernameValue
                })
            });

            if (!response.ok) {
                throw new Error('Failed to get SRP authentication challenge');
            } //End if

            startTimer(); // Start the timer to reload the page after 60 seconds

            let responseData = await response.json();
            responseData = responseData.data || null;

            if (!responseData) {
                throw new Error('Invalid response data for SRP authentication challenge');
            } // End if

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
    } // Function ends

    /**
     * Function to handle the form submission for the SRP challenge.
     * It hashes the password using SHA-256 and updates the hidden
     * challenge value input before allowing the form to submit.
     *
     * @returns {Promise<boolean>} - Returns true to allow the form to submit.
     **/
    async function submitChallengeForm() {
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password_code');
        const challengeValueInput = document.getElementById('challenge_value');
        
        // Set the actual password value in the hidden challenge value input
        let passKey = atob(poolKey) + usernameInput.value + ':' + passwordInput.value;

        // Hash with SHA256 and set the hashed value in the challenge value input
        let passKeyHash = await hashEncrypt(passKey, 'SHA-256');

        // Update the challenge value input with the hashed password
        let challengeValue = challengeValueInput.value ? JSON.parse(challengeValueInput.value) : {};
        challengeValue.PASSKEY_HASH = passKeyHash;
        challengeValueInput.value = JSON.stringify(challengeValue);// Add the hashed password to the challenge value input

        return true; // Allow the form to submit
    } // Function ends

    /**
     * Utility function to hash a value using the Web Crypto API
     *
     * @param {string} value - The value to be hashed
     * @param {string} key - The hashing algorithm (default is 'SHA-256')
     *
     * @returns {Promise<string>} - A promise that resolves to the hex string of the hash
     **/
    async function hashEncrypt(value, key='SHA-256')
    {
        let encoder = new TextEncoder();
        let data = encoder.encode(value);

        // Hash the data using the specified key (e.g., SHA-256)
        let hashBuffer = await crypto.subtle.digest(key, data);

        // Convert the hash buffer to a hex string
        return Array.from(new Uint8Array(hashBuffer))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    } //Function ends
</script>

<script>
    function startTimer() {
        let count = 60; // Initial count value (e.g., 60 seconds)
        const intervalId = setInterval(() => {
            count--; // Decrement the count
            
            if (count <= 0) {
                clearInterval(intervalId); // Stops the timer
                window.location.reload(); // Reloads the page when the count reaches 0
            }
        }, 1000);
    } // Function ends
</script>
