<form method="POST" action="{{ route('cognito.action.login.submit') }}" name="login-form">
    @csrf

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror"
                name="username" value="{{ old('username') }}"
                required autocomplete="email" autofocus />

            @error('username')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

        <div class="col-md-6">
            <input id="password" type="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password" required autocomplete="off" />

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6 offset-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                    name="remember" id="remember"
                    value="{{ old('remember') ? 'checked' : '' }}" />

                <label class="form-check-label" for="remember">
                    {{ __('Remember Me') }}
                </label>
            </div>
        </div>
    </div>

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

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            @if (Route::has('cognito.form.password.forgot'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.password.forgot') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            @endif
        </div>
    </div>

    @if (config('cognito.allow_passkeys'))
    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <a type="button" id="auth-passkeys-button" class="btn btn-secondary">
                {{ __('Login with Passkey') }}
            </a>

            <a type="button" id="auth-magic-link-button" class="btn btn-secondary float-end">
                {{ __('Login with Magic Link') }}
            </a>
        </div>
    </div>
    @endif

</form>

<script>
    const urlPasskeyAuthChallenge = "{{Route::has('cognito.action.auth.passkey.challenge') ? (route('cognito.action.auth.passkey.challenge')) : 'null'}}";
    const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';

    const form = document.querySelector('form[name="login-form"]');
    const formData = new FormData(form);

    const authPasskeysButton = document.getElementById('auth-passkeys-button');
    authPasskeysButton.addEventListener('click', function() {
        authPasskey();
    });

    var challengeParameters = {
        "challenge": 32, // Replace with actual challenge from server
        "credId": "base64url-encoded-credential-id" // Replace with actual credential ID from server
    };

    // Function to handle the passkey authentication process
    async function authPasskey() {
        try {
            var response = await fetch(urlPasskeyAuthChallenge, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    username: 'amit.dhongde+test124@gmail.com', // Replace with actual username from form input if needed
                    challenge_type: 'WEB_AUTHN' // Indicate that this is a WebAuthn challenge request
                })
            });

            if (!response.ok) {
                throw new Error('Failed to get passkey authentication challenge');
            }

            responseData = await response.json();
            responseData = responseData.data || {};
            challengeParameters = responseData.ChallengeParameters || {};

            // Build the options for navigator.credentials.get() based on the challenge parameters received from the server
            var signinOptions = JSON.parse(challengeParameters.CREDENTIAL_REQUEST_OPTIONS);
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

            // // Send the authentication response to the server for verification
            // const response = await fetch(urlPasskeyCompleteEndpoint, {
            //     method: 'POST',
            //     headers: {
            //             'Content-Type': 'application/json',
            //             'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
            //         },
            //     credentials: 'same-origin',
            //     body: JSON.stringify({
            //         credential: credentialToCognitoPayload(credential)
            //     })
            // });

            // if (!response.ok) {
            //     throw new Error('Passkey authentication failed');
            // }

            // // On successful authentication, reload the page or redirect as needed
            // window.location.reload();

        } catch (error) {
            console.error('Error authenticating passkey:', error);
            alert('Passkey authentication failed. Check the console for details.');
        }
    }
</script>
