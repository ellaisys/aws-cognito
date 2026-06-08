@csrf

@if((isset($challengeNameValue) && ($challengeNameValue != 'NONE')))
    <input type="hidden" id="challenge_name" name="challenge_name" value="{{ $challengeNameValue ?? '' }}" required />
    <input type="hidden" id="session" name="session" value="{{ $sessionValue ?? '' }}" />
    <input type="hidden" id="challenge_params" name="challenge_params" value="{{ $challengeParamsValue ?? '' }}" />
    <input type="hidden" id="challenge_value"  name="challenge_value" required />
    <input type="hidden" id="username" name="username" value="{{ old('username', $usernameValue) }}" required />
@endif

@if((isset($challengeNameValue) && ($challengeNameValue != 'NONE')))
    <div class="row mb-3">
        <div class="col-md-6 offset-md-4">
        <h4>{{ __('cognito::messages.challenge.'.strtolower($challengeNameValue)) }}</h4>
        </div>
    </div>

    <div class="row mb-3">
        <label for="temp_username" class="col-md-4 col-form-label text-md-end">{{ __('cognito::messages.email_address') }}</label>

        <div class="col-md-6">
            <input type="email" class="form-control"
                id="temp_username" name="temp_username"
                value="{{ $usernameValue }}" disabled />
        </div>
    </div>
@endif

@push('cognito-challenge-passcode')
    @if (in_array($challengeNameValue, [
        'SOFTWARE_TOKEN_MFA', 'SMS_MFA', 'SMS_OTP',
        'EMAIL_OTP', 'PASSWORD_VERIFIER', 'PASSWORD']))
        <x-cognito::challenge.password />
    @endif
@endPush

@pushif((isset($challengeNameValue) && ($challengeNameValue != 'NONE')),'cognito-challenge-scripts')
    <script>
        // Large prime number
        const N = BigInt("{{ '0x' . $srpParameters['N_HEX'] }}");
        
        //Generator value
        const g = BigInt("{{ '0x' . $srpParameters['G_HEX'] }}");

        const poolKey = "{{ base64_encode($cognitoPoolName) }}";
        const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';
        const challengeNameValue = document.getElementById('challenge_name');
        const challengeValue = document.getElementById('challenge_value');
        const challengeParamsValue = document.getElementById('challenge_params');
        const sessionValue = document.getElementById('session');
        const usernameValue = document.getElementById('username');
        const frmChallenge = document.getElementById('{{ $challengeFormName ?? 'auth-challenge-form' }}');

        /**
         * Event listener for DOMContentLoaded to trigger the appropriate challenge response
         * generation based on the challenge name received from the server.
         */
        document.addEventListener("DOMContentLoaded", function(event) {
            // Attach the form submission handler to the challenge form
            frmChallenge.addEventListener('submit', handleFormSubmit);

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

            if (['PASSWORD_SRP', 'PASSWORD', 'SOFTWARE_TOKEN_MFA',
                'SMS_MFA', 'SMS_OTP', 'EMAIL_OTP'].includes(challengeNameValue.value)) {
                let elemPasscode = document.getElementById('pass_code');
                challengeValue.value = elemPasscode.value;
                elemPasscode.value = ''; // Clear the passcode input for security reasons
                elemPasscode.disabled = true; // Disable the passcode input
            }

            if (!frmChallenge.checkValidity()) {
                frmChallenge.reportValidity(); // Show validation errors if the form is not valid
                return; // Stop form submission if validation fails
            } else {
                // Submit the form
                frmChallenge.submit();
            } //End if
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
            if (!session) {
                console.error('Session parameters not found');
                return;
            }

            // Step 2: Get the private ephemeral value from localStorage
            let privateEphemeral = (session) ? localStorage.getItem(session) : null;
            if (privateEphemeral) { localStorage.removeItem(session); }

            // Get the challenge parameters value and parse it as JSON
            let challengeParams = challengeParamsValue.value ? JSON.parse(challengeParamsValue.value) : null;
            if (!challengeParams) {
                console.error('Challenge parameters not found');
                return;
            }

            console.log('message:', computedMessageVerifier(true, false));

            // Build the response object to be sent back to the server
            let responseData = {
                'PASSWORD_CLAIM_SECRET_BLOCK': challengeParams?.SECRET_BLOCK || '',
                'TIMESTAMP': getCognitoTimestamp(),
                'DEVICE_KEY': challengeParams?.DEVICE_KEY || '',

                'PASSKEY_HASH':passKeyHash,
                'MESSAGE_BASE64': computedMessageVerifier(true),
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
            try {
                const elemPasscode = document.getElementById('pass_code');
                
                // Set the actual password value in the hidden challenge value input
                let passKey = atob(poolKey) + usernameValue.value + ':' + elemPasscode.value;

                // Hash with SHA256 and set the hashed value in the challenge value input
                let passKeyHash = await hashEncrypt(passKey, 'SHA-256');

                // Get the challenge parameters value and parse it as JSON
                let challengeParams = challengeParamsValue.value ? JSON.parse(challengeParamsValue.value) : null;
                if (!challengeParams) {
                    throw new Error("Challenge parameters not found");
                }

                // Update the challenge value input with the hashed password
                let payload = {
                    'PASSWORD_CLAIM_SECRET_BLOCK': challengeParams?.SECRET_BLOCK || '',
                    'TIMESTAMP': getCognitoTimestamp(),
                    'PASSKEY_HASH': passKeyHash,
                    'MESSAGE_BASE64': computedMessageVerifier(false)
                };
                challengeValue.value = JSON.stringify(payload);// Add the hashed password to the challenge value input
                elemPasscode.value = ''; // Clear the password input for security reasons
                elemPasscode.disabled = true; // Disable the password input

                return true; // Allow the form to submit after handling the challenge
            } catch (error) {
                console.error('Error generating password verifier:', error);
                return false; // Prevent form submission if there was an error
            }
        } // Function ends

        function computedMessageVerifier(isDeviceAuth = false, isBase64 = true) {
            try{
                // Get Base64 encoded Secret Block
                let challengeParams = challengeParamsValue.value ? JSON.parse(challengeParamsValue.value) : null;
                if (!challengeParams) {
                    throw new Error("Challenge parameters not found");
                }
                let secretBlock = challengeParams?.SECRET_BLOCK || null;
                let secretBlockBase64 = secretBlock ? atob(secretBlock) : null;
                if (!secretBlockBase64) {
                    throw new Error("Secret block not found in challenge parameters");
                }

                //Build the message
                let message = '';
                if (isDeviceAuth) {
                    let deviceGroupKey = localStorage.getItem('d-grp');
                    if (!deviceGroupKey) {
                        throw new Error("Device group key not found in localStorage");
                    }
                    let deviceKey = challengeParams?.DEVICE_KEY || localStorage.getItem('d-key');
                    if (!deviceKey) {
                        throw new Error("Device key not found in localStorage");
                    }
                    message += deviceGroupKey + deviceKey;
                } else {
                    message += atob(poolKey) + challengeParams?.USER_ID_FOR_SRP;
                }
                message += secretBlockBase64;
                message += getCognitoTimestamp();

                return isBase64 ? btoa(message) : message;
            } catch (error) {
                console.error('Error computing message verifier:', error);
                return null;
            }
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
         * Utility function to get the current timestamp in the format required by AWS Cognito.
         * Cognito expects the timestamp to be in the format: "EEE MMM d HH:mm:ss 'UTC' yyyy"
         * For example: "Wed Mar 3 12:34:56 UTC 2021"
         * This function constructs the timestamp string by getting the current date and time in UTC,
         * and formatting it according to the required structure.
         * @return {string} - The current timestamp formatted for AWS Cognito
         **/
        function getCognitoTimestamp() {
            // Get the current date and time in UTC
            const now = new Date();

            const weekdays = [
                    'Sun', 'Mon', 'Tue', 'Wed',
                    'Thu', 'Fri', 'Sat'
                ];

            const months = [
                    'Jan', 'Feb', 'Mar', 'Apr',
                    'May', 'Jun', 'Jul', 'Aug',
                    'Sep', 'Oct', 'Nov', 'Dec'
                ];

            // Build the timestamp string in the format required by Cognito
            let weekday = weekdays[now.getUTCDay()];
            let month = months[now.getUTCMonth()];
            let day = now.getUTCDate();
            let hours = String(now.getUTCHours()).padStart(2, '0');
            let minutes = String(now.getUTCMinutes()).padStart(2, '0');
            let seconds = String(now.getUTCSeconds()).padStart(2, '0');
            let year = now.getUTCFullYear();

            return `${weekday} ${month} ${day} ${hours}:${minutes}:${seconds} UTC ${year}`;
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
@endPushIf
