<form method="POST" id="auth-challenge-form" action="{{ route('cognito.action.auth.challenge.submit') }}">
    @csrf

    @php
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
    @endphp

    @if($data && isset($data['status']) && $data['status'] == 'challenge')
    <div class="row mb-3">
        <div class="col-md-6 offset-md-4">
        @php
            switch ($challengeNameValue) {
                case 'DEVICE_SRP_AUTH':
                    echo '<h4>Generating the Device Challenge</h4>';
                    break;

                case 'DEVICE_PASSWORD_VERIFIER':
                    echo '<h4>Responding to the Device Challenge</h4>';
                    break;

                case 'SOFTWARE_TOKEN_MFA':
                case 'SMS_MFA':
                case 'SMS_OTP':
                case 'EMAIL_OTP':
                    echo '<h4>Provide the TOTP Code</h4>';
                    break;

                case 'WEB_AUTHN':
                    echo '<h4>Validating the WebAuthn Challenge</h4>';
                    break;

                default:
                    echo '<h4>'.__('Authentication Challenge').'</h4>';
                    break;
            }
        @endphp
        <span class="text-muted d-none">{{ $data ? json_encode($data) : '' }}</span><br/>
        </div>
    </div>
    @endif

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input type="hidden" id="challenge_name" name="challenge_name" value="{{ $challengeNameValue ?? '' }}" required />
            <input type="hidden" id="session" name="session" value="{{ $sessionValue ?? '' }}" />
            <input type="hidden" id="challenge_params" name="challenge_params" value="{{ $challengeParamsValue ?? '' }}" />

            @if (in_array($challengeNameValue, ['WEB_AUTHN', 'DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER', 'PASSWORD_SRP', 'PASSWORD_VERIFIER']))
                <input type="hidden" id="challenge_value"  name="challenge_value" required />
            @endif

            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror @if($usernameValue) is-valid @endif"
                name="username" value="{{ old('username', $usernameValue) }}"
                @if($usernameValue) readonly autocomplete="off" @else required autocomplete="email" autofocus @endif
                />
        </div>
    </div>

    @if (in_array($challengeNameValue, ['PASSWORD_VERIFIER', 'PASSWORD']))
        <div class="row mb-3">
            <label for="password_code" class="col-md-4 col-form-label text-md-end"
                id="challenge_value_label">{{ __('Password') }}</label>

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
    @elseif (in_array($challengeNameValue, ['SOFTWARE_TOKEN_MFA', 'SMS_MFA', 'SMS_OTP', 'EMAIL_OTP']))
        <div class="row mb-3">
            <label for="challenge_value" class="col-md-4 col-form-label text-md-end" id="challenge_value_label">{{ __('Code') }}</label>

            <div class="col-md-6">
                <input id="challenge_value" type="text"
                    class="form-control @error('challenge_value') is-invalid @enderror"
                    name="challenge_value" placeholder="{{ $challengeValueText ?? '' }}" required autocomplete="off" />

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
            <button type="submit" id="auth-challenge-form-submit-button" class="btn btn-primary"
                onclick="handleFormSubmit(event);">
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

@if (in_array($challengeNameValue, ['DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER', 'PASSWORD_SRP', 'PASSWORD_VERIFIER', 'WEB_AUTHN']))
<script>
    const poolKey = "{{ base64_encode($namePool) }}";
    const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';

    const N = BigInt("5809605995369958062791915965639201402176612226902900533702900882779736177890990861472094774477339581147373410185646378328043729800750470098210924487866935059164371588168047540943981644516632755067501626434556398193186628990071248660819361205119793693985433297036118232914410171876807536457391277857011849897410207519105333355801121109356897459426271845471397952675959440793493071628394122780510124618488232602464649876850458861245784240929258426287699705312584509625419513463605155428017165714465363094021609290561084025893662561222573202082865797821865270991145082200656978177192827024538990239969175546190770645685893438011714430426409338676314743571154537142031573004276428701433036381801705308659830751190352946025482059931306571004727362479688415574702596946457770284148435989129632853918392117997472632693078113129886487399347796982772784615865232621289656944284216824611318709764535152507354116344703769998514148343807");
    const g = BigInt("2");

    const challengeNameValue = document.getElementById('challenge_name');
    const challengeValue = document.getElementById('challenge_value');
    const challengeParamsValue = document.getElementById('challenge_params');
    const sessionValue = document.getElementById('session');
    const usernameValue = document.getElementById('username');
    const frmChallenge = document.getElementById('auth-challenge-form');

    /**
     * Event listener for DOMContentLoaded to trigger the appropriate challenge response
     * generation based on the challenge name received from the server.
     */
    document.addEventListener("DOMContentLoaded", function(event) {
        if (challengeNameValue.value == 'DEVICE_SRP_AUTH') {
            generateDeviceSRPAuthChallenge();
        }

        if (challengeNameValue.value == 'DEVICE_PASSWORD_VERIFIER') {
            generateDeviceVerifier();
        }

        if (challengeNameValue.value == 'PASSWORD_SRP') {
            generatePasswordSRPAuth();
        }

        if (challengeNameValue.value == 'WEB_AUTHN') {
            validateWebAuthnChallenge();
        }
    });

    /**
     * Form submission handler for the authentication challenge form. It
     * checks the type of challenge and processes the steps ahead.
     * @param {*} event
     */
    async function handleFormSubmit(event) {
        event.preventDefault(); // Prevent the default form submission

        if (challengeNameValue.value == 'PASSWORD_VERIFIER') {
            let response = await generatePasswordVerifier(); // Call the function to handle the PASSWORD_VERIFIER challenge
        }
        frmChallenge.submit(); // Submit the form for other challenge types without additional processing
    } // Function ends

    /**
     * Function to generate the SRP authentication response for the
     * device challenge. Build a random secret ephemeral value 'a',
     * compute the corresponding 'A' value, and construct the response.
     */
    function generateDeviceSRPAuthChallenge() {
        // 1. Generate a random secret ephemeral 'a' (at least 32 bytes recommended)
        const randomBytes = CryptoJS.lib.WordArray.random(128);
        const a = BigInt("0x" + randomBytes.toString(CryptoJS.enc.Hex));

        // 2. Calculate A = g^a % N
        // Note: BigInt modular exponentiation is needed here.
        // For browser/node: A = BigInt(g)**BigInt(a) % BigInt(N)
        const A = modPow(g, a, N);

        // 3. Generate a random number to store private ephemeral
        let session = sessionValue.value || null;
        if (session) {
            localStorage.setItem(session, a.toString(16));
        }

        // Get the challenge parameters
        let challengeParams = challengeParamsValue.value || '{}';
        challengeParams = JSON.parse(challengeParams);
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
        challengeValue.value = JSON.stringify(responseData);
        frmChallenge.submit();
    } // Function ends

    /**
     * Function to generate the response for the device password
     * verifier challenge. In a full implementation, you would
     * need to ensure that the client-side logic correctly follows
     * the SRP protocol and securely handles all cryptographic
     * operations and sensitive data.
     */
    function generateDeviceVerifier() {
        // Step 1: Construct the passkey hash
        let passKey = localStorage.getItem('d-grp');
        passKey += localStorage.getItem('d-key') + ":";
        passKey += localStorage.getItem('d-secret');
        let passKeyHash = CryptoJS.SHA256(passKey).toString(CryptoJS.enc.Hex);

        // Get the session value
        let session = sessionValue.value || null;

        // Step 2: Get the private ephemeral value from localStorage
        let privateEphemeral = (session) ? localStorage.getItem(session) : null;
        if (privateEphemeral) { localStorage.removeItem(session); }

        // Get the challenge parameters
        if (!challengeParamsValue?.value) {
            console.error('Challenge parameters not found');
            return;
        }

        // Build the response object to be sent back to the server
        let responseData = {
            'PASSKEY_HASH':passKeyHash,
            'PRIVATE_KEY':privateEphemeral,
            'DEVICE_GROUP_KEY':localStorage.getItem('d-grp')
        };

        // After computing the response, set it in the hidden input field and submit the form
        challengeValue.value = JSON.stringify(responseData);
        frmChallenge.submit();
    } // Function ends

    /**
     * Function to fetch the SRP authentication challenge from the server.
     *
     * It sends the user's email to the server and expects to receive the
     * challenge name, challenge parameters, and session token in response.
     * The function then updates the form with the received challenge data,
     * allowing the user to proceed with the authentication process.
     **/
    async function generatePasswordSRPAuth() {
        try {
            let response = await fetch(frmChallenge.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    username: usernameValue?.value
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
            sessionValue.value = responseData?.session_token || '';
            challengeNameValue.value = responseData?.challenge_name || '';
            challengeParamsValue.value = JSON.stringify(responseData?.challenge_params || {});
        } catch (error) {
            console.error('Error authenticating SRP:', error);
        } // End try-catch
    } // Function ends

    /**
     * Function to handle the WebAuthn challenge authentication process.
     * It retrieves the challenge parameters from the server, prompts
     * the user to authenticate using their passkey credential, and
     * submits the authentication response back to the server.
     **/
    async function validateWebAuthnChallenge() {
        try {
            let objChallengeParams = challengeParamsValue.value ? JSON.parse(challengeParamsValue.value) : null;
            if (objChallengeParams) {
                /**
                 * Build the options for navigator.credentials.get() based
                 * on the challenge parameters received from the server
                 */
                let signinOptions = JSON.parse(objChallengeParams.CREDENTIAL_REQUEST_OPTIONS);
                signinOptions.challenge = Uint8Array.from(atob(signinOptions.challenge.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                signinOptions.allowCredentials = signinOptions.allowCredentials.map(cred => {
                    return {
                        ...cred,
                        id: Uint8Array.from(atob(cred.id.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0))
                    };
                });

                // Prompt the user to authenticate using their passkey credential
                let credential = await navigator.credentials.get({
                    mediation: 'optional',
                    password: true,
                    publicKey: signinOptions
                });

                challengeValue.value = credential ? JSON.stringify(credential) : '';
                frmChallenge.submit();
            } else {
                throw new Error("Missing challenge params");
            } // End if
        } catch (error) {
            console.error('Error authenticating passkey:', error);
        } // End try-catch
    } // Function ends

    /**
     * Function to handle the form submission for the PASSWORD_VERIFIER
     * challenge.
     * It hashes the password using SHA-256 and updates the hidden
     * challenge value input before allowing the form to submit.
     *
     **/
    async function generatePasswordVerifier() {
        const passwordInput = document.getElementById('password_code');
        
        // Set the actual password value in the hidden challenge value input
        let passKey = atob(poolKey) + usernameValue.value + ':' + passwordInput.value;

        // Hash with SHA256 and set the hashed value in the challenge value input
        let passKeyHash = await hashEncrypt(passKey, 'SHA-256');

        // Update the challenge value input with the hashed password
        let payload = {
            'PASSKEY_HASH': passKeyHash
        };
        challengeValue.value = JSON.stringify(payload);// Add the hashed password to the challenge value input
        passwordInput.value = ''; // Clear the password input for security reasons
        passwordInput.disabled = true; // Disable the password input

        return true; // Allow the form to submit after handling the challenge
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

    /**
     * Utility function to perform modular exponentiation (base^exponent mod modulus)
     * This function is essential for the SRP protocol calculations, where we need
     * to compute values like A = g^a mod N.
     * @param {BigInt} base - The base value (e.g., g in SRP)
     * @param {BigInt} exponent - The exponent value (e.g., a in SRP)
     * @param {BigInt} modulus - The modulus value (e.g., N in SRP)
     * @returns {BigInt} - The result of (base^exponent) mod modulus
     **/
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
    } // Function ends

    /**
     * Function to start a timer that reloads the page after a specified duration.
     * In this case, the timer is set for 60 seconds. When the timer expires,
     * the page will automatically reload, which can be useful for resetting
     * the authentication process if the user takes too long to respond to
     * the challenge.
     *
     * @param {number} counter - The duration of the timer in seconds (default is 60 seconds)
     **/
    function startTimer(counter = 60) {
        const intervalId = setInterval(() => {
            counter--; // Decrement the count
            
            if (counter <= 0) {
                clearInterval(intervalId); // Stops the timer
                window.location.reload(); // Reloads the page when the count reaches 0
            }
        }, 1000);
} // Function ends
</script>
@endif
