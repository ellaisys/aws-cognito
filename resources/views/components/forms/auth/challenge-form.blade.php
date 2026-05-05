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
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror @if($usernameValue) is-valid @endif"
                name="username" value="{{ old('username', $usernameValue) }}"
                @if($usernameValue) readonly @else required autocomplete="email" autofocus @endif
                />
        </div>
    </div>

    @if ($challengeNameValue == 'WEB_AUTHN')
        <input id="challenge_value" type="hidden" name="challenge_value" />
    @else
        <div class="row mb-3">
            <label for="challenge_value" class="col-md-4 col-form-label text-md-end" id="challenge_value_label">{{ __('Code') }}</label>

            <div class="col-md-6">
                <input id="challenge_value" type="text"
                    class="form-control @error('challenge_value') is-invalid @enderror"
                    name="challenge_value" required autocomplete="off" />

                @error('challenge_value')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
    @endif

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" class="btn btn-primary">
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
    const urlPasskeyAuthChallenge = "{{Route::has('cognito.action.auth.passkey.challenge') ? (route('cognito.action.auth.passkey.challenge')) : 'null'}}";
    const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';

    window.addEventListener('load', (event) => {
        getChallengeData();
    });

    // Function to handle the passkey authentication process
    async function getChallengeData() {
        try {
            const challengeNameValue = document.getElementById('challenge_name');
            const challengeValue = document.getElementById('challenge_value');
            const sessionValue = document.getElementById('session');

            var response = await fetch(urlPasskeyAuthChallenge, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    username: "{{ $usernameValue }}",
                    challenge_name: "{{ $challengeNameValue }}"
                })
            });

            if (!response.ok) {
                throw new Error('Failed to get passkey authentication challenge');
            } //End if

            responseData = await response.json();
            responseData = responseData.data || {};

            //Set the session value received from the server
            sessionValue.value = responseData.Session || '';
            challengeNameValue.value = responseData.ChallengeName || '';

            // If the challenge is for WebAuthn, set the challenge value and submit the form
            if (responseData.ChallengeName == 'WEB_AUTHN') {
                // Build the options for navigator.credentials.get() based on the challenge parameters received from the server
                var signinOptions = JSON.parse(responseData.ChallengeParameters.CREDENTIAL_REQUEST_OPTIONS);
                signinOptions.challenge = Uint8Array.from(atob(signinOptions.challenge.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                signinOptions.allowCredentials = signinOptions.allowCredentials.map(cred => {
                    return {
                        ...cred,
                        id: Uint8Array.from(atob(cred.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0))
                    };
                });

                // Prompt the user to authenticate using their passkey credential
                var credential = await navigator.credentials.get({
                    mediation: 'optional',
                    password: true,
                    publicKey: signinOptions
                });

                console.log('Passkey authentication response:', credential);

                challengeValue.value = credential ? JSON.stringify(credential) : '';
                document.getElementById('auth-challenge-form').submit();
            } else {
                var challengeParam = responseData.ChallengeParameters || {};
                challengeValue.placeholder = `${challengeParam.CODE_DELIVERY_DELIVERY_MEDIUM} sent to ${challengeParam.CODE_DELIVERY_DESTINATION}`;
            } // End if
        } catch (error) {
            console.error('Error authenticating passkey:', error);
        } // End try-catch
    }
</script>
