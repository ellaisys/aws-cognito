@props([
    'challengeNameValue' => 'NONE'
])

@if ($includeGMP)
    <x-cognito::common.js.gmp
        :includeGMP="$includeGMP" />
@endif

@if ($includeCryptoJS || $includeCryptoUtils)
    <x-cognito::common.js.crypto
        :includeCryptoJS="$includeCryptoJS"
        :includeCryptoUtils="$includeCryptoUtils" />
@endif

<input type="hidden" name="device_key" id="device_key" value="" disabled />

@push('cognito-device-auth-scripts')
    @if ($includeGMP)
        @stack('cognito-common-gmp-scripts')
    @endif

    @if ($includeCryptoJS || $includeCryptoUtils)
        @stack('cognito-common-crypto-scripts')
    @endif

    <script>
        /**
         * Event listener for DOMContentLoaded to trigger the appropriate
         * actions.
         */
        document.addEventListener("DOMContentLoaded", function(event) {
            // Add event listeners to all buttons with the data-role attribute set to "device-auth"
            const elemsDeviceAuth = document.querySelectorAll('[data-role="device-auth"]');
            elemsDeviceAuth.forEach(button => {
                try {
                    // Initialize and check device registration status
                    let deviceService = new DeviceService();

                    // Element action attribute to determine the action to be performed
                    let dataAction = button?.attributes['data-action']?.value?.toLowerCase() ?? 'undefined';

                    if ((deviceService?.isDeviceRegistered) && dataAction === 'register') {
                        button.disabled = true;
                    } else if ((!deviceService?.isDeviceRegistered) && dataAction === 'delete') {
                        button.disabled = true;
                    } else if ((deviceService?.isDeviceRegistered) && dataAction === 'validate') {
                        let deviceKey = deviceService?.deviceData['d-key'] ?? '';

                        // Set the device key value in the hidden input field
                        // and enable it for submission.
                        let elemDeviceKey = document.getElementById('device_key');
                        elemDeviceKey.value = deviceKey;
                        elemDeviceKey.disabled = false;
                    } else {
                        button.disabled = false;
                    } //End if
                } catch (error) {
                    console.error('Error processing device auth button:', error);
                    button.disabled = false;
                } // Try ends

                button.addEventListener('click', async function() {
                    // Disable the button to prevent multiple clicks
                    this.disabled = true;

                    // Get the action from the data-action attribute and validate it
                    let dataAction = this.attributes['data-action'] ? (this.attributes['data-action'].value).toLowerCase() : null;
                    if (!dataAction) {
                        console.warn('No action specified for device auth button.');
                        this.disabled = false;
                        return;
                    } //End if

                    const service = new DeviceService();
                    if (dataAction === 'register') { // Register a new device
                        let response = await service.register();

                        // Disable on success, re-enable on failure
                        this.disabled = response;
                    } else if (dataAction === 'delete') { // Delete an existing device
                        let response = await service.delete();

                        // Re-enable the button after deletion
                        this.disabled = response;
                    } else if (dataAction === 'validate') { // No action
                        this.disabled = false;
                    } else { // Handle unknown action
                        console.warn('Unknown action for device action button.');

                        // Re-enable the button if action is unknown
                        this.disabled = false;
                    } //End if
                });
            });
        });

        /**
         * Class to handle the device passkey registration and deletion
         * process. It communicates with the server to obtain the device
         * details, complete the registration, and delete existing keys.
         */
        class DeviceService {

            /**
             * Constructor to initialize the DeviceService class
             * with necessary parameters for secure communication with
             * the server during the passkey registration process.
             */
            constructor() {
                this.csrfToken = "{{ csrf_token() }}";
                this.secureCode = "{{ $secureCode ?? '' }}";
                this.newDeviceData = "{{ $newDeviceData ?? '' }}";
            }

            /**
             * Main function to register the device for the user. It
             * orchestrates the entire registration process by
             * communicating with the server and using the Device API.
             */
            async register() {
                try {
                    // Check if the device is already registered
                    if (this.isDeviceRegistered) {
                        throw new Error('This device is already registered for the user.');
                    }

                    // Get the device registration options from the server
                    let confirmResponse = await this.#confirmDevice();

                    if (confirmResponse && confirmResponse.data) {
                        this.#alert('Device registered successfully.', 'success');
                        return true;
                    }

                    return false;
                } catch (error) {
                    console.error('Error registering device:', error);
                    this.#alert('Device registration failed. Check the console for details.', 'error');
                    return false;
                }
            } //Function end

            /**
             * Function to delete an existing device for the user. It
             * communicates with the server to delete the device and
             * removes the corresponding data from local storage.
             */
            async delete() {
                try {
                    // Check if the device is already registered
                    if (!this.isDeviceRegistered) {
                        throw new Error('This device is not registered for the user.');
                    }

                    // Read the data from local store
                    let deviceData = this.deviceData;

                    // Signal the authenticator about the deleted credential
                    let deletePayload = await this.#deleteDevice(deviceData['d-key']);
                    if (deletePayload) {
                        // Remove the data from local store
                        localStorage.removeItem(this.secureCode);

                        this.#alert('Device deleted successfully.', 'success');
                        return true;
                    } //End if

                    return false;
                } catch (error) {
                    console.error('Error deleting device:', error);
                    this.#alert('Device deletion failed. Check the console for details.', 'error');
                    return false;
                }
            } //Function end

            /**
             * Function to start the passkey registration process by
             * requesting options from the server
             */
            async #confirmDevice() {
                try {
                    //Build the device secret verifier payload
                    const {payload, deviceSecret} = await this.#buildConfirmDevicePayload();

                    // Get the passkey registration options from the server
                    let response = await fetch("{{ $urlDeviceConfirmEndpoint ?? '' }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        throw new Error('Failed to confirm device');
                    }

                    // Store the device secret in local storage for later use
                    this.#deviceSecret = deviceSecret;

                    return await response.json();
                } catch (error) {
                    console.error('Error with device confirmation:', error);
                    throw error;
                }
            } //Function end

            /**
             * Function to delete the device from the server and signal
             * the client to remove the credential from the storage.
             */
            async #deleteDevice(deviceKey) {
                try {
                    let deleteResponse = await fetch("{{ $urlDeviceDeleteEndpoint ?? '' }}", {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            device_key: deviceKey
                        })
                    });

                    if (!deleteResponse.ok) {
                        throw new Error('Failed to delete device');
                    }

                    return await deleteResponse.json();
                } catch (error) {
                    console.error('Error deleting device:', error);
                    throw error;
                }
            } //Function end

            /**
             * Function to build the payload for device confirmation,
             * which includes generating a random device secret,
             * computing the device hash, and constructing the verifier
             * using the device hash and a random salt.
             * The payload is then sent to the server to complete the
             * device registration.
             */
            async #buildConfirmDevicePayload() {
                try {
                    // Get the device data from local storage
                    let deviceData = this.deviceData;

                    // Generate a secret, random salt for the device
                    const deviceSecret = CryptoUtils.randomBase64(40);
                    const saltHex = this.#deviceSalt;

                    // Build device hash
                    const deviceHash = await this.getDeviceHash(deviceSecret);

                    // Build the verifier using the device hash and salt
                    let xHash = await CryptoUtils.hexHash(saltHex + deviceHash);
                    let xBigInt = CryptoUtils.convHexToBigInt(xHash);
                    let verifierBigInt = GMP.gmp_powm(CryptoUtils.g_BigInt, xBigInt, CryptoUtils.N_BigInt);
                    const verifierHex = CryptoUtils.convBigIntToUnsignedHex(verifierBigInt);

                    // Build the payload to be sent to the server for device confirmation
                    let payload = {
                        'device_key': deviceData['d-key'],
                        'device_name': this.#deviceName,
                        'device_config': JSON.stringify({
                            'DeviceSecretVerifierConfig': {
                                'Salt': CryptoUtils.convHexToBase64(saltHex),
                                'PasswordVerifier': CryptoUtils.convHexToBase64(verifierHex)
                            }
                        })};
    
                    return {payload, deviceSecret};
                } catch (error) {
                    console.error('Error generating device payload:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Getter for device data stored in local storage. It retrieves
             * the device data for the user based on the secure code and
             * user key. If no data is found, it checks for new device data.
             * @returns {Object} - The device data object containing keys
             * and group information.
             * @throws {Error} - Throws an error if no device data is found.
             */
            get deviceData() {
                try {
                    let deviceData = localStorage.getItem(this.secureCode);
                    if (!deviceData) {
                        // If not found, check if new device data is available
                        deviceData = this.newDeviceData ?? null;
                        if (!deviceData) {
                            throw new Error('No device data found for the user in local storage');
                        } // End if
                    } // End if
                    return JSON.parse(atob(deviceData));
                } catch (error) {
                    console.error('Error retrieving device data from local storage:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Setter for device data stored in local storage. It saves
             * the device data for the user based on the secure code and
             * user key. The data is stored as a JSON string.
             * @param {Object} deviceData - The device data object to be
             * stored in local storage.
             * @throws {Error} - Throws an error if there is an issue
             * saving the data to local storage.
             */
            set deviceData(deviceData) {
                try {
                    deviceData = btoa(JSON.stringify(deviceData));
                    localStorage.setItem(this.secureCode, deviceData);
                } catch (error) {
                    console.error('Error saving device data to local storage:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Getter to check if the device is registered. It verifies
             * if the device data contains the necessary keys and group
             * information.
             * @returns {boolean} - Returns true if the device is registered,
             * false otherwise.
             * @throws {Error} - Throws an error if there is an issue
             * retrieving the device data.
             */
            get isDeviceRegistered() {
                try {
                    const deviceData = this.deviceData;
                    return Boolean(deviceData && deviceData['d-key'] && deviceData['d-secret'] && deviceData['d-grp']);
                } catch (error) {
                    console.error('Device not registered:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Getter to retrieve the device name. It attempts to get the
             * device name from the navigator object, falling back to
             * 'Unknown Device' if not available.
             * @returns {string} - The device name.
             * @throws {Error} - Throws an error if there is an issue
             * retrieving the device name.
             */
            get #deviceName() {
                try {
                    return navigator.appCodeName || 'Unknown Device';
                } catch (error) {
                    console.error('Error retrieving device name:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Getter to generate a random salt for the device. It uses
             * the CryptoUtils class to generate a random hex string of
             * 16 bytes (128 bits) and converts it to an unsigned hex
             * format.
             * @returns {string} - The generated device salt in unsigned
             * hex format.
             * @throws {Error} - Throws an error if there is an issue
             * generating the salt.
             */
            get #deviceSalt() {
                try {
                    // Generate a random salt for the device of 16 bytes (128 bits)
                    let saltHex = CryptoUtils.randomHex(16);

                    // Convert to unsigned hex format to prevent negative BigInteger interpretation
                    return CryptoUtils.convHexToUnsignedHex(saltHex);
                } catch (error) {
                    console.error('Error generating device salt:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Setter to update the device secret. It updates the device
             * data in local storage with the new device secret.
             * @param {string} deviceSecret - The new device secret.
             * @throws {Error} - Throws an error if there is an issue
             * updating the device secret.
             */
            set #deviceSecret(deviceSecret) {
                try {
                    let deviceData = this.deviceData;
                    deviceData['d-secret'] = deviceSecret;

                    // Save the updated device data back to local storage
                    this.deviceData = deviceData;
                } catch (error) {
                    console.error('Error setting device secret:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Function to compute the device hash using the device
             * group key, device key, and device secret. The hash is
             * computed using SHA-256 and is used as part of the device
             * authentication process.
             **/
            async getDeviceHash(deviceSecret = null) {
                try {
                    let deviceData = this.deviceData;
                    let deviceGroupKey = deviceData['d-grp'];
                    let deviceKey = deviceData['d-key'];
                    deviceSecret = deviceSecret ?? deviceData['d-secret'];

                    if (!deviceGroupKey || !deviceKey || !deviceSecret) {
                        throw new Error('Device data is incomplete in local storage');
                    } // End if

                    let secret = `${deviceGroupKey}${deviceKey}:${deviceSecret}`;

                    return await CryptoUtils.hashEncrypt(secret);
                } catch (error) {
                    console.error('Error computing device hash:', error);
                    throw error;
                } // Try ends
            } //Function end

            /**
             * Function to display an alert message. It uses the CognitoAlert
             * class if available, otherwise falls back to the default alert.
             * @param {string} message - The message to display.
             * @param {string} type - The type of alert ('info', 'success', 'error').
             */
            #alert(message, type = 'info') {
                if (typeof CognitoAlert !== 'undefined') {
                    let alertBox = new CognitoAlert();
                    if (type === 'success') {
                        alertBox.success(message);
                    } else if (type === 'error') {
                        alertBox.error(message);
                    } else {
                        alertBox.info(message);
                    }
                } else {
                    // Fallback to default alert if CognitoAlert is not available
                    alert(message);
                } //End if
            } //Function end

        } //Class end

        @if ($challengeNameValue !== 'NONE')
        /**
         * Class to handle the device authentication challenges, including
         * DEVICE_SRP_AUTH and DEVICE_PASSWORD_VERIFIER. It extends the
         * CognitoChallenge class to utilize common challenge handling
         * functionality while implementing specific logic for device
         * authentication.
         */
        class DeviceChallenge extends CognitoChallenge {
            // Default constructor
            constructor() {
                super();
                this.service = new DeviceService();
            }

            /**
             * Function to generate the SRP authentication response for the
             * device challenge. Build a random secret ephemeral value 'a',
             * compute the corresponding 'A' value, and construct the response.
             * @returns {string} - The JSON string containing the SRP
             * authentication response to be sent to the server
             * @throws {Error} - Throws an error during the process.
             */
            async authenticate() {
                try {
                    // Generate a random secret ephemeral 'a' (at least 32 bytes recommended)
                    const randomBytes = CryptoJS.lib.WordArray.random(128);
                    let a_BigInt = CryptoUtils.convHexToBigInt(randomBytes.toString(CryptoJS.enc.Hex));

                    // Calculate A = g^a % N
                    // Note: BigInt modular exponentiation is needed here.
                    // For browser/node: A = BigInt(g)**BigInt(a) % BigInt(N)
                    const A_BigInt = GMP.gmp_powm(CryptoUtils.g_BigInt, a_BigInt, CryptoUtils.N_BigInt);

                    // Generate a random number to store private ephemeral
                    let session = sessionValue.value || null;
                    if (session) {
                        localStorage.setItem(session, a_BigInt.toString(16));
                    } // End if

                    // Build the response object to be sent back to the server
                    let responseData = {
                        'DEVICE_KEY': this.service?.deviceData['d-key'] || '',
                        'SRP_A': A_BigInt.toString(16).toUpperCase()
                    };

                    // Return the JSON string
                    return JSON.stringify(responseData);
                } catch (error) {
                    console.error('Error generating device SRP auth challenge:', error);
                    throw error;
                } // Try ends
            } // Function end

            /**
             * Function to generate the response for the device password
             * verifier challenge. In a full implementation, you would
             * need to ensure that the client-side logic correctly follows
             * the SRP protocol and securely handles all cryptographic
             * operations and sensitive data.
             * @returns {string} - The JSON string containing the device
             * password verifier response to be sent to the server
             * @throws {Error} - Throws an error during the process.
             */
            async verifier() {
                try {
                    // Get the challenge parameters value and parse it as JSON
                    let objChallengeParams = this.ChallengeParams;

                    // Build the response object to be sent back to the server
                    let responseData = {
                        'PASSWORD_CLAIM_SECRET_BLOCK': objChallengeParams?.SECRET_BLOCK || '',
                        'TIMESTAMP': this.CognitoTimestamp,
                        'DEVICE_KEY': objChallengeParams?.DEVICE_KEY || '',
                    };

                    // Build the signature for the password claim.
                    let signature = await this.#buildPasswordClaimSignature();
                    if (!signature) {
                        throw new Error("Failed to build password claim signature");
                    } // End if

                    // Return the JSON string
                    return JSON.stringify({...responseData, ...signature});
                } catch (error) {
                    console.error('Error generating device verifier:', error);
                    throw error;
                } // Try ends
            } // Function end

            /**
             * Function to compute the message verifier for the DEVICE_PASSWORD_VERIFIER
             * challenge. It constructs the message based on the challenge parameters
             * and encodes it in Base64 format.
             * @returns {string} - The Base64 encoded message verifier to be sent to the server
             * @throws {Error} - Throws an error during the process.
             **/
            get #DeviceMessage() {
                try{
                    // Read the data from local store
                    let deviceData = this.service.deviceData;

                    // Get Base64 encoded Secret Block
                    let objChallengeParams = this.ChallengeParams;
                    let secretBlock = objChallengeParams?.SECRET_BLOCK || null;
                    let secretBlockBase64 = secretBlock ? atob(secretBlock) : null;
                    if (!secretBlockBase64) {
                        throw new Error("Secret block not found in challenge parameters");
                    } // End if

                    let deviceGroupKey = deviceData['d-grp'];
                    if (!deviceGroupKey) {
                        throw new Error("Device group key not found in localStorage");
                    } // End if

                    // Get the device key
                    let deviceKey = objChallengeParams?.DEVICE_KEY || deviceData['d-key'];
                    if (!deviceKey) {
                        throw new Error("Device key not found in challenge parameters");
                    } // End if

                    //Build the message
                    let message = '';
                    message += deviceGroupKey + deviceKey;
                    message += secretBlockBase64;
                    message += this.CognitoTimestamp;

                    return btoa(message);
                } catch (error) {
                    console.error('Error computing message verifier:', error);
                    throw error;
                } // Try ends
            } // Function end

            /**
             * Function to build the password claim signature for the
             * DEVICE_PASSWORD_VERIFIER challenge. It follows the SRP
             * protocol to compute the necessary values and generates
             * the signature using HMAC with the derived key.
             *
             * Note: For production implementation, we have implemented
             * all cryptographic operations securely and the sensitive
             * data is handled appropriately.
             *
             * @returns {Object} - An object containing the
             * PASSWORD_CLAIM_SIGNATURE in Base64 format, or additional
             * debug information if the signature generation fails.
             * @throws {Error} - Throws an error during the process.
             **/
            async #buildPasswordClaimSignature() {
                try {
                    // Initialize signature variable
                    let signature = null;
                    const deviceMessage = this.#DeviceMessage;

                    // Get the session value
                    let session = sessionValue.value || null;
                    if (!session) {
                        throw new Error("Session parameters not found");
                    }

                    // Get the private ephemeral value from localStorage
                    let privateEphemeral = (session) ? localStorage.getItem(session) : null;
                    if (privateEphemeral) { localStorage.removeItem(session); }
                    let a_BigInt = CryptoUtils.convHexToBigInt(privateEphemeral);

                    // Calculate A = g^a % N
                    // Note: BigInt modular exponentiation is needed here.
                    // For browser/node: A = BigInt(g)**BigInt(a) % BigInt(N)
                    const A_BigInt = GMP.gmp_powm(CryptoUtils.g_BigInt, a_BigInt, CryptoUtils.N_BigInt);

                    // Get the challenge parameters value and parse it as JSON
                    let objChallengeParams = this.ChallengeParams;

                    // Get the salt value and convert it to hex format
                    const salt = objChallengeParams?.SALT ?? null;
                    const srpB = objChallengeParams?.SRP_B ?? null;
                    if (!salt || !srpB) {
                        throw new Error("Salt or SRP_B not found in challenge parameters");
                    } // End if
                    let saltHex = CryptoUtils.convBigIntToUnsignedHex(GMP.gmp_init(salt, 16));
                    let B_BigInt = GMP.gmp_init(srpB, 16);

                    // Calculate u = H(A || B)
                    let uHash = await CryptoUtils.hexHash(
                        CryptoUtils.convBigIntToUnsignedHex(A_BigInt) +
                        CryptoUtils.convBigIntToUnsignedHex(B_BigInt)
                    );

                    // Construct the passkey hash
                    const deviceHash = await this.service?.getDeviceHash();
                    let xHash = await CryptoUtils.hexHash(saltHex + deviceHash);
                    let xBigInt = CryptoUtils.convHexToBigInt(xHash);

                    // Calculate base and exponent for S_USER calculation
                    const K_BigInt = await CryptoUtils.K_BigInt();

                    // Calculate the base (SRP_B - k * g^x) in BigInt
                    let gModPowX_BigInt = GMP.gmp_powm(CryptoUtils.g_BigInt, xBigInt, CryptoUtils.N_BigInt);
                    let kgx_BigInt = GMP.gmp_mul(K_BigInt, gModPowX_BigInt);
                    let sBase_BigInt = GMP.gmp_sub(B_BigInt, kgx_BigInt);

                    // Calculate the exponent (a + u * x) in BigInt
                    let sExp_BigInt = GMP.gmp_add(
                            a_BigInt,
                            GMP.gmp_mul(CryptoUtils.convHexToBigInt(uHash), xBigInt)
                        );

                    // Calculate S_USER = (SRP_B - k * g^x)^(a + u * x) % N
                    let S_USER_BigInt = GMP.gmp_powm(sBase_BigInt, sExp_BigInt, CryptoUtils.N_BigInt);

                    // Calculate HKDF to derive the key for signing the message
                    let hkdfHex = await CryptoUtils.hkdf(
                        CryptoUtils.convHexToBytes(CryptoUtils.convBigIntToUnsignedHex(S_USER_BigInt)),
                        CryptoUtils.convHexToBytes(CryptoUtils.convHexToUnsignedHex(uHash))
                    );

                    // Generate the signature using HMAC with the derived key
                    signature = await CryptoUtils.hashHmacHex(
                        CryptoUtils.convBase64ToUint8Array(deviceMessage),
                        CryptoUtils.convHexToBytes(hkdfHex)
                    );

                    if (signature) {
                        return {
                            'PASSWORD_CLAIM_SIGNATURE':CryptoUtils.convHexToBase64(signature)
                        };
                    } else {
                        return {
                            'PASSKEY_HASH':deviceHash,
                            'MESSAGE_BASE64':deviceMessage,
                            'PRIVATE_KEY':privateEphemeral,
                            'DEVICE_GROUP_KEY':this.service?.deviceData['d-grp'] || ''
                        }
                    } // End if
                } catch (error) {
                    console.error('Error building password claim signature:', error);
                    throw error;
                } // Try ends
            } // Function end

        } // Class end
        @endif
    </script>
@endpush
