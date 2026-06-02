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
    @endphp

    {{ $data ? json_encode($data) : '' }}

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input type="hidden" id="challenge_name" name="challenge_name" value="{{ $challengeNameValue }}" />
            <input type="hidden" id="session" name="session" value="{{ $sessionValue }}" />
            <input type="hidden" id="challenge_params" name="challenge_params" value="{{ ($data && isset($data['challenge_params'])) ? json_encode($data['challenge_params']) : '' }}" />
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror @if($usernameValue) is-valid @endif"
                name="username" value="{{ old('username', $usernameValue) }}"
                @if($usernameValue) readonly autocomplete="off" @else required autocomplete="email" autofocus @endif
                />
        </div>
    </div>

    @if (in_array($challengeNameValue, ['WEB_AUTHN', 'DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER']))
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
            @if (!in_array($challengeNameValue, ['WEB_AUTHN', 'DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER']))
            <button type="submit" class="btn btn-primary">
                {{ __('Login') }}
            </button>
            @endif

            @if (Route::has('cognito.form.register'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.register') }}">
                    {{ __('Register?') }}
                </a>
            @endif
        </div>
    </div>
</form>

@if (in_array($challengeNameValue, ['WEB_AUTHN', 'EMAIL_OTP', 'SMS_OTP']))
<script>
    const urlPasskeyAuthChallenge = "{{Route::has('cognito.action.auth.passkey.challenge') ? (route('cognito.action.auth.passkey.challenge')) : 'null'}}";
    const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';

    document.addEventListener("DOMContentLoaded", function(event) {
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
@endif

@if (in_array($challengeNameValue, ['DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER']))
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
<script>
    const challengeNameValue = document.getElementById('challenge_name');

    const N = BigInt("5809605995369958062791915965639201402176612226902900533702900882779736177890990861472094774477339581147373410185646378328043729800750470098210924487866935059164371588168047540943981644516632755067501626434556398193186628990071248660819361205119793693985433297036118232914410171876807536457391277857011849897410207519105333355801121109356897459426271845471397952675959440793493071628394122780510124618488232602464649876850458861245784240929258426287699705312584509625419513463605155428017165714465363094021609290561084025893662561222573202082865797821865270991145082200656978177192827024538990239969175546190770645685893438011714430426409338676314743571154537142031573004276428701433036381801705308659830751190352946025482059931306571004727362479688415574702596946457770284148435989129632853918392117997472632693078113129886487399347796982772784615865232621289656944284216824611318709764535152507354116344703769998514148343807");
    const g = BigInt("2");

    document.addEventListener("DOMContentLoaded", function(event) {
        if (challengeNameValue.value == 'DEVICE_SRP_AUTH') {
             generateDeviceSRPAuth();
        }

        if (challengeNameValue.value == 'DEVICE_PASSWORD_VERIFIER') {
            generateDeviceVerifier();
        }
    });

    /**     
     * Function to generate the SRP authentication response for the
     * device challenge. Build a random secret ephemeral value 'a',
     * compute the corresponding 'A' value, and construct the response.
     */
    function generateDeviceSRPAuth() {
        // 1. Generate a random secret ephemeral 'a' (at least 32 bytes recommended)
        const randomBytes = CryptoJS.lib.WordArray.random(128);
        const a = BigInt("0x" + randomBytes.toString(CryptoJS.enc.Hex));
    
        // 2. Calculate A = g^a % N
        // Note: BigInt modular exponentiation is needed here.
        // For browser/node: A = BigInt(g)**BigInt(a) % BigInt(N)
        const A = modPow(g, a, N);

        // 3. Generate a random number to store private ephemeral
        let session = document.getElementById('session');
        session = session.value || null;
        if (session) {
            localStorage.setItem(session, a.toString(16));
        }

        // Get the challenge parameters
        let challengeParams = document.getElementById('challenge_params');
        challengeParams = JSON.parse(challengeParams?.value || '{}');
        if (!challengeParams) {
            console.error('Challenge parameters not found');
            return;
        }
    
        // Build the response object to be sent back to the server
        let responseData = {
            'USERNAME': challengeParams?.USER_ID_FOR_SRP,
            'DEVICE_KEY': localStorage.getItem('d-key') || '',
            'SRP_A': A.toString(16).toUpperCase()
        };

        // After computing the response, set it in the hidden input field and submit the form
        const challengeValue = document.getElementById('challenge_value');
        challengeValue.value = JSON.stringify(responseData);
        document.getElementById('auth-challenge-form').submit();
    }

    function generateDeviceVerifier() {
        // Step 1: Construct the passkey hash
        let passKey = localStorage.getItem('d-grp');
        passKey += localStorage.getItem('d-key') + ":";
        passKey += localStorage.getItem('d-secret');
        let passKeyHash = CryptoJS.SHA256(passKey).toString(CryptoJS.enc.Hex);

        // Get the session value
        let session = document.getElementById('session');
        session = session.value || null;

        // Step 2: Get the private ephemeral value from localStorage
        let privateEphemeral = (session) ? localStorage.getItem(session) : null;
        if (privateEphemeral) { localStorage.removeItem(session); }

        // Get the challenge parameters
        let challengeParams = document.getElementById('challenge_params');
        if (!challengeParams?.value) {
            console.error('Challenge parameters not found');
            return;
        }

        // Build the response object to be sent back to the server
        let responseData = {
            'PASSKEY_HASH':passKeyHash,
            'PRIVATE_KEY':privateEphemeral,
            'DEVICE_GROUP_KEY':localStorage.getItem('d-grp'),
            'CHALLENGE_PARAMS':challengeParams?.value
        };

        // After computing the response, set it in the hidden input field and submit the form
        const challengeValue = document.getElementById('challenge_value');
        challengeValue.value = JSON.stringify(responseData);
        document.getElementById('auth-challenge-form').submit();
    }

    function modPow(base, exponent, modulus) {
        if (modulus === 1n) {
            return 0n;
        }

        let result = 1n;
        let currentBase = base % modulus;
        let currentExponent = exponent;

        while (currentExponent > 0n) {
            if (currentExponent % 2n === 1n) {
            result = (result * currentBase) % modulus;
            }

            currentExponent = currentExponent / 2n;
            currentBase = (currentBase * currentBase) % modulus;
        }

        return result;
    }
</script>
@endif
