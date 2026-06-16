@csrf

<x-cognito::common.js-scripts />
<x-cognito::common.js.gmp />
<x-cognito::common.js.crypto />

<x-cognito-passkey-webauthn
    :challengeNameValue="$challengeNameValue" />

<x-cognito-device-auth
    :challengeNameValue="$challengeNameValue"
    :includeGMP="false"
    :includeCryptoJS="false"
    :includeCryptoUtils="false" />

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
        <x-cognito::challenge.password 
            :challengeNameValue="$challengeNameValue"
            :challengeValuePlaceholder="$challengeValuePlaceholder"
        />
    @endif
@endPush

@pushif((isset($challengeNameValue) && ($challengeNameValue != 'NONE')),'cognito-challenge-scripts')
    @stack('cognito-common-scripts')
    @stack('cognito-common-gmp-scripts')
    @stack('cognito-common-crypto-scripts')
    <script>
        const challengeNameValue = document.getElementById('challenge_name');
        const challengeValue = document.getElementById('challenge_value');
        const challengeParamsValue = document.getElementById('challenge_params');
        const sessionValue = document.getElementById('session');
        const usernameValue = document.getElementById('username');
        const frmChallenge = document.getElementById('{{ $challengeFormName ?? 'auth-challenge-form' }}');

        // Process all buttons with the data-action attribute set to "challenge-submit"
        const elemsChallengeSubmit = document.querySelectorAll('[data-action="challenge-submit"]');
        elemsChallengeSubmit.forEach((button) => {
            let btnRole = button.attributes['data-role'].value || null;
            if (!btnRole) { return; }
            btnRole = btnRole.toUpperCase();

            if (['WEB_AUTHN', 'DEVICE_SRP_AUTH',
                'DEVICE_PASSWORD_VERIFIER', 'NONE'].includes(btnRole)) {
                    // Disable and hide the button for these challenge
                    // types since they are handled automatically
                    button.disabled = true;
                    button.style.display = 'none';
                }
            else if (['PASSWORD_SRP', 'PASSWORD',
                'SOFTWARE_TOKEN_MFA', 'SMS_MFA',
                'SMS_OTP', 'EMAIL_OTP'].includes(btnRole)) {
                    button.addEventListener('click', function(event) {
                        // Set passcode value to challenge_value input before form submission
                        let elemPasscode = document.getElementById('pass_code');
                        challengeValue.value = elemPasscode.value;

                        // Clear the passcode input for security reasons
                        elemPasscode.value = ''; 
                        elemPasscode.disabled = true;

                        // Call the form submission handler
                        handleFormSubmit(event);
                    });
                }
            else {
                button.addEventListener('click', function(event) {
                    // Call the form submission handler for other challenge types
                    handleFormSubmit(event);
                });
            } //End if
        });

        /**
         * Event listener for DOMContentLoaded to trigger the appropriate challenge response
         * generation based on the challenge name received from the server.
         */
        document.addEventListener("DOMContentLoaded", function(event) {
            // Attach the form submission handler to the challenge form
            // frmChallenge.addEventListener('submit', handleFormSubmit);

            if (['WEB_AUTHN', 'DEVICE_SRP_AUTH', 'DEVICE_PASSWORD_VERIFIER', 'NONE'].includes(challengeNameValue.value)) {
                // Automatically process the challenge for these types without user interaction
                handleFormSubmit(event);
            } //End if
        });

        /**
         * Form submission handler for the authentication challenge form. It
         * checks the type of challenge and processes the steps ahead.
         * @param {*} event
         */
        async function handleFormSubmit(event) {
            try {
                // Prevent the default form submission
                event.preventDefault(); 

                // Process the challenge based on the challenge name
                await processChallenge();

                if (!frmChallenge.checkValidity()) {
                    // Show validation errors if the form is not valid
                    frmChallenge.reportValidity(); 
                    return; // Stop form submission if validation fails
                } else {
                    // Submit the form
                    frmChallenge.submit();
                } //End if                
            } catch (error) {
                console.error("Error handling form submission:", error);
                throw error;
            }
        } // Function ends

        /**
         * Function to process the authentication challenge based on the
         * challenge name. It generates the appropriate response for each
         * challenge type and updates the data before form submission.
         */
        async function processChallenge() {
            try {
                if (challengeNameValue.value == 'DEVICE_SRP_AUTH') {
                    let challenge = new DeviceChallenge();
                    let response = await challenge.authenticate();
                    challengeValue.value = response;
                } // End if

                if (challengeNameValue.value == 'DEVICE_PASSWORD_VERIFIER') {
                    let challenge = new DeviceChallenge();
                    let response = await challenge.verifier();
                    challengeValue.value = response;
                } // End if

                if (challengeNameValue.value == 'PASSWORD_SRP') {
                    let challenge = new PasswordSRPChallenge();
                    let response = await challenge.authenticate();
                    challengeValue.value = response;
                } // End if

                if (challengeNameValue.value == 'PASSWORD_VERIFIER') {
                    let challenge = new PasswordSRPChallenge();
                    let response = await challenge.verifier();
                    challengeValue.value = response;
                } // End if

                if (challengeNameValue.value == 'WEB_AUTHN') {
                    let challenge = new WebAuthnChallenge();
                    let response = await challenge.verifier();
                    challengeValue.value = response;
                } // End if
            } catch (error) {
                console.error("Error processing challenge:", error);
                throw error;
            } // End try-catch
        } // Function ends

        /**
         * Class to handle the various Cognito authentication challenges, including
         * the SRP authentication for devices, password verification, and WebAuthn
         * challenges. This class encapsulates the logic for generating the appropriate
         * responses to the challenges based on the parameters received from the server,
         * and securely handling the cryptographic operations required for the SRP protocol.
         */
        class CognitoChallenge {
            // Large prime number
            static N_BigInt = BigInt("{{ '0x' . $srpParameters['N_HEX'] }}");
            
            // Generator value
            static g_BigInt = BigInt("{{ '0x' . $srpParameters['G_HEX'] }}");

            constructor() {
                if (new.target === CognitoChallenge) {
                    throw new TypeError("Cannot construct CognitoChallenge instances directly");
                }

                this.poolKey = "{{ base64_encode($cognitoPoolName) }}";
                this.csrfToken = "{{ csrf_token() }}";
                this.secureCode = "{{ $secureCode ?? '' }}";
                this.userkeyB64encoded = null;
            }

            /**
             * Utility function to hash a value using the Web Crypto API
             *
             * @param {string} value - The value to be hashed
             * @param {string} key - The hashing algorithm (default is 'SHA-256')
             *
             * @returns {Promise<string>} - A promise that resolves to the hex string of the hash
             **/
            async hashEncrypt(value, key = "SHA-256")
            {
                let encoder = new TextEncoder();
                let data = encoder.encode(value);

                // Hash the data using the specified key (e.g., SHA-256)
                let hashBuffer = await crypto.subtle.digest(key, data);

                // Convert the hash buffer to a hex string
                return Array.from(new Uint8Array(hashBuffer))
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('');
            } // Function ends

            /**
             * Utility function to get the current timestamp in the format required by AWS Cognito.
             * Cognito expects the timestamp to be in the format: "EEE MMM d HH:mm:ss 'UTC' yyyy"
             * For example: "Wed Mar 3 12:34:56 UTC 2021"
             * This function constructs the timestamp string by getting the current date and time in UTC,
             * and formatting it according to the required structure.
             * @return {string} - The current timestamp formatted for AWS Cognito
             **/
            get CognitoTimestamp() {
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

            get ChallengeParams() {
                try {
                    let challengeParams = challengeParamsValue.value ? JSON.parse(challengeParamsValue.value) : null;
                    if (!challengeParams) {
                        throw new Error("Challenge parameters not found");
                    }
                    return challengeParams;
                } catch (error) {
                    console.error('Error parsing challenge parameters:', error);
                    throw error;
                }
            } // Function ends

        } // Class ends

        /**
         * Class to handle the PASSWORD_SRP and PASSWORD_VERIFIER
         * authentication challenges. It generates the appropriate
         * responses based on the SRP protocol and the parameters
         * received from the server, securely handling the password
         * hashing and verifier generation.
         */
        class PasswordSRPChallenge extends CognitoChallenge {
            // Default constructor
            constructor() {
                super();
            }

            /**
             * Function to fetch the SRP authentication challenge from the server.
             *
             * It sends the user's email to the server and expects to receive the
             * challenge name, challenge parameters, and session token in response.
             * The function then updates the form with the received challenge data,
             * allowing the user to proceed with the authentication process.
             **/
            async authenticate() {
                try {
                    let response = await fetch(frmChallenge.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            username: usernameValue?.value
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Failed to get SRP authentication challenge');
                    } //End if

                    this.#startTimer(); // Start the timer to reload the page after 60 seconds

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
                    throw error;
                } // End try-catch
            } // Function ends

            /**
             * Function to handle the form submission for the PASSWORD_VERIFIER
             * challenge.
             * It hashes the password using SHA-256 and updates the hidden
             * challenge value input before allowing the form to submit.
             * @returns {Promise<string>} - A promise that resolves to the JSON string
             * containing the password verifier response to be sent to the server
             * @throws {Error} - Throws an error during the process.
             **/
            async verifier() {
                try {
                    // Get the challenge parameters value and parse it as JSON
                    let objChallengeParams = this.ChallengeParams;

                    // Get the password input element
                    const elemPasscode = document.getElementById('pass_code');
                    
                    // Set the actual password value in the hidden challenge value input
                    let passKey = atob(this.poolKey) + objChallengeParams?.USER_ID_FOR_SRP + ':' + elemPasscode.value;

                    // Hash with SHA256 and set the hashed value in the challenge value input
                    let passKeyHash = await this.hashEncrypt(passKey);

                    // Update the challenge value input with the hashed password
                    let payload = {
                        'PASSWORD_CLAIM_SECRET_BLOCK': objChallengeParams?.SECRET_BLOCK || '',
                        'TIMESTAMP': this.CognitoTimestamp,
                        'PASSKEY_HASH': passKeyHash,
                        'MESSAGE_BASE64': this.#PasswordMessage
                    };

                    /**
                     * Clear the password input and disable it to enhance
                     * security, ensuring that the plaintext password is
                     * not left in the input field and cannot be modified
                     * after hashing.
                     */
                    elemPasscode.value = '';
                    elemPasscode.disabled = true;

                    return JSON.stringify(payload);
                } catch (error) {
                    console.error('Error generating password verifier:', error);
                    throw error;
                }
            } // Function ends

            /**
             * Function to compute the message verifier for the PASSWORD_VERIFIER
             * challenge. It constructs the message based on the challenge parameters
             * and encodes it in Base64 format.
             * @returns {string} - The Base64 encoded message verifier to be sent to the server
             * @throws {Error} - Throws an error during the process.
             **/
            get #PasswordMessage() {
                try{
                    // Get Base64 encoded Secret Block
                    let objChallengeParams = this.ChallengeParams;
                    let secretBlock = objChallengeParams?.SECRET_BLOCK || null;
                    let secretBlockBase64 = secretBlock ? atob(secretBlock) : null;
                    if (!secretBlockBase64) {
                        throw new Error("Secret block not found in challenge parameters");
                    }

                    //Build the message
                    let message = '';
                    message += atob(this.poolKey) + objChallengeParams?.USER_ID_FOR_SRP;
                    message += secretBlockBase64;
                    message += this.CognitoTimestamp;

                    return btoa(message);
                } catch (error) {
                    console.error('Error computing message verifier:', error);
                    throw error;
                }
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
            #startTimer(counter = 60) {
                const intervalId = setInterval(() => {
                    counter--; // Decrement the count
                    
                    if (counter <= 0) {
                        clearInterval(intervalId); // Stops the timer
                        window.location.reload(); // Reloads the page when the count reaches 0
                    }
                }, 1000);
            } // Function ends
        } // Class ends
    </script>
    @stack('cognito-passkey-webauthn-scripts')
    @stack('cognito-device-auth-scripts')
@endPushIf