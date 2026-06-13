@pushif((true),'cognito-device-auth-scripts')
    <script>
        // Add event listeners to all buttons with the data-role attribute set to "device-auth"
        const elemsDeviceAuth = document.querySelectorAll('[data-role="device-auth"]');
        elemsDeviceAuth.forEach(button => {
            button.addEventListener('click', async function() {
                // Disable the button to prevent multiple clicks
                this.disabled = true;

                // Get the action from the data-action attribute and validate it
                let dataAction = this.attributes['data-action'] ? (this.attributes['data-action'].value).toLowerCase() : null;
                if (!dataAction || (dataAction !== 'register' && dataAction !== 'delete')) {
                    console.warn('No action specified for device auth button. Use "register" or "delete" as data-action value.');
                    this.disabled = false;
                    return;
                } //End if

                if (dataAction === 'register') { // Register a new device
                    let service = new DeviceService();
                    let response = await service.register();

                    // Disable on success, re-enable on failure
                    this.disabled = response;
                } else if (dataAction === 'delete') { // Delete an existing device
                    // Get the user key from the data attribute
                    let userkeyB64encoded = this.attributes['data-userkey'].value;
                    let service = new DeviceService();
                    await service.delete(userkeyB64encoded);

                    // Re-enable the button after deletion
                    this.disabled = false;
                } else { // Handle unknown action
                    console.warn('Unknown action for device action button. Use "register" or "delete" as data-action value.');

                    // Re-enable the button if action is unknown
                    this.disabled = false;
                } //End if
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
                this.userkeyB64encoded = "{{ $userkeyB64encoded ?? '' }}";
            }

            /**
             * Main function to register passkeys for the user. It orchestrates
             * the entire registration process by communicating with the
             * server and using the WebAuthn API.
             */
            async register() {
                try {
                    // Get the passkey registration options from the server
                    let confirmPayload = await this.#confirmDevice();

                    // Convert the server response to the format required for FIDO2 registration
                    let publicKeyOptions = this.#getPublicKeyCreationOptions(confirmPayload);

                    if (completePayload) {
                        this.#alert('Passkey registered successfully.', 'success');
                        return true;
                    }
                    return false;
                } catch (error) {
                    console.error('Error registering passkey:', error);
                    this.#alert('Passkey registration failed. Check the console for details.', 'error');
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
                    // Read the data from local store
                    let deviceData = localStorage.getItem(this.secureCode + this.userkeyB64encoded);
                    if (!deviceData) {
                        throw new Error('No data found for the device in local storage');
                    }
                    deviceData = JSON.parse(deviceData);

                    // Signal the authenticator about the deleted credential
                    let deletePayload = await this.#deleteDevice(deviceData['d-key']);
                    if (deletePayload) {
                        // Remove the data from local store
                        localStorage.removeItem(this.secureCode + this.userkeyB64encoded);

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
                    let payload = this.#buildDeviceSecretVerifier();

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


            #buildDeviceSecretVerifier() {
                try {
                    // Generate a random secret verifier for the device
                    const randomBytes = new Uint8Array(32);
                    window.crypto.getRandomValues(randomBytes);
                    const deviceSecretVerifier = btoa(String.fromCharCode(...randomBytes));
    
                    // Store the device secret verifier in local storage for later use
                    localStorage.setItem(this.secureCode + 'device-secret-verifier', deviceSecretVerifier);
    
                    return {
                        device_secret_verifier: deviceSecretVerifier
                    };
                } catch (error) {
                    console.error('Error generating device secret verifier:', error);
                    throw error;
                }
            } //Function end

            /**
             * Function to convert the server response into the format required
             * for WebAuthn registration
             */
            #getPublicKeyCreationOptions(startPayload) {

                let rawOptions = startPayload && startPayload.data
                    ? startPayload.data.CredentialCreationOptions || startPayload.data.credentialCreationOptions || startPayload.data
                    : null;

                if (!rawOptions) {
                    throw new Error('CredentialCreationOptions not found in start response');
                }

                if (typeof rawOptions === 'string') {
                    rawOptions = JSON.parse(rawOptions);
                }

                let publicKeyOptions = rawOptions.publicKey ? rawOptions.publicKey : rawOptions;
                publicKeyOptions.challenge = this.#base64urlToUint8Array(publicKeyOptions.challenge);

                if (publicKeyOptions.user && publicKeyOptions.user.id) {
                    publicKeyOptions.user.id = this.#base64urlToUint8Array(publicKeyOptions.user.id);
                }

                if (Array.isArray(publicKeyOptions.excludeCredentials)) {
                    publicKeyOptions.excludeCredentials = publicKeyOptions.excludeCredentials.map((credentialDescriptor) => {
                        return Object.assign({}, credentialDescriptor, {
                            id: this.#base64urlToUint8Array(credentialDescriptor.id)
                        });
                    });
                }

                return publicKeyOptions;
            } //Function end

            /**
             * Utility functions for base64url encoding/decoding and converting
             * credentials to a format suitable for sending to the server
             */
            #base64urlToUint8Array(base64url) {
                let padding = '='.repeat((4 - (base64url.length % 4)) % 4);
                let base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
                let binaryString = window.atob(base64);

                return Uint8Array.from(binaryString, c => c.charCodeAt(0));
            } //Function end

            /**
             * Convert the credential object returned by the WebAuthn API into a format
             * that can be sent to the server for registration completion
             */
            #bufferToBase64url(buffer) {
                var bytes = new Uint8Array(buffer);
                var binary = '';
                bytes.forEach((byte) => {
                    binary += String.fromCharCode(byte);
                });

                return window.btoa(binary)
                    .replace(/\+/g, '-')
                    .replace(/\//g, '_')
                    .replace(/=+$/g, '');
            } //Function end

            /**
             * Convert the credential object returned by the WebAuthn API into a format
             * that can be sent to the server for registration completion
             */
            #credentialToCognitoPayload(credential) {
                return JSON.stringify({
                    id: credential?.id,
                    type: credential?.type,
                    rawId: this.#bufferToBase64url(credential?.rawId),
                    authenticatorAttachment: credential?.authenticatorAttachment,
                    response: {
                        clientDataJSON: this.#bufferToBase64url(credential?.response?.clientDataJSON),
                        attestationObject: this.#bufferToBase64url(credential?.response?.attestationObject),
                        transports: typeof credential?.response?.getTransports === 'function'
                            ? credential?.response?.getTransports()
                            : []
                    },
                    clientExtensionResults: credential?.getClientExtensionResults()
                }, null, 2);
            } //Function end

            #alert(message, type = 'info') {

                let alertBox = new CognitoAlert();
                if (alertBox) {
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
                }
            }

        } //Class end

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
            }

            /**
             * Function to generate the SRP authentication response for the
             * device challenge. Build a random secret ephemeral value 'a',
             * compute the corresponding 'A' value, and construct the response.
             * @returns {string} - The JSON string containing the SRP
             * authentication response to be sent to the server
             * @throws {Error} - Throws an error during the process.
             */
            async DeviceSRPAuth() {
                try {
                    // Get the challenge parameters value and parse it as JSON
                    let objChallengeParams = this.ChallengeParams;

                    // Generate a random secret ephemeral 'a' (at least 32 bytes recommended)
                    const randomBytes = CryptoJS.lib.WordArray.random(128);
                    const a = BigInt("0x" + randomBytes.toString(CryptoJS.enc.Hex));

                    // Calculate A = g^a % N
                    // Note: BigInt modular exponentiation is needed here.
                    // For browser/node: A = BigInt(g)**BigInt(a) % BigInt(N)
                    const A = GMP.gmp_powm(CognitoChallenge.g_BigInt, a, CognitoChallenge.N_BigInt);

                    // Generate a random number to store private ephemeral
                    let session = sessionValue.value || null;
                    if (session) {
                        localStorage.setItem(session, a.toString(16));
                    } // End if

                    // Read the data from local store
                    this.userkeyB64encoded = btoa(objChallengeParams?.USER_ID_FOR_SRP);
                    let deviceData = localStorage.getItem(this.secureCode + this.userkeyB64encoded);
                    if (!deviceData) {
                        throw new Error('No passkey data found for the device in local storage');
                    }
                    deviceData = JSON.parse(deviceData);

                    // Build the response object to be sent back to the server
                    let responseData = {
                        'USERNAME': objChallengeParams?.USER_ID_FOR_SRP,
                        'DEVICE_KEY': deviceData['d-key'] || '',
                        'SRP_A': A.toString(16).toUpperCase()
                    };                    

                    // Return the JSON string
                    return JSON.stringify(responseData);
                } catch (error) {
                    console.error('Error generating device SRP auth challenge:', error);
                    throw error;
                }
            } // Function ends

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

                    // Read the data from local store
                    this.userkeyB64encoded = btoa(objChallengeParams?.USERNAME);
                    let deviceData = localStorage.getItem(this.secureCode + this.userkeyB64encoded);
                    if (!deviceData) {
                        throw new Error('No passkey data found for the device in local storage');
                    }
                    deviceData = JSON.parse(deviceData);

                    // Construct the passkey hash
                    let passKey = deviceData['d-grp'] + deviceData['d-key'] + ":" + deviceData['d-secret'];
                    let passKeyHash = CryptoJS.SHA256(passKey).toString(CryptoJS.enc.Hex);

                    // Get the session value
                    let session = sessionValue.value || null;
                    if (!session) {
                        throw new Error("Session parameters not found");
                    }

                    // Get the private ephemeral value from localStorage
                    let privateEphemeral = (session) ? localStorage.getItem(session) : null;
                    if (privateEphemeral) { localStorage.removeItem(session); }

                    // Build the response object to be sent back to the server
                    let responseData = {
                        'PASSWORD_CLAIM_SECRET_BLOCK': objChallengeParams?.SECRET_BLOCK || '',
                        'TIMESTAMP': this.CognitoTimestamp,
                        'DEVICE_KEY': objChallengeParams?.DEVICE_KEY || '',

                        'PASSKEY_HASH':passKeyHash,
                        'MESSAGE_BASE64': this.#DeviceMessage,
                        'PRIVATE_KEY':privateEphemeral,
                        'DEVICE_GROUP_KEY':deviceData['d-grp']
                    };

                    // Return the JSON string
                    return JSON.stringify(responseData);
                } catch (error) {
                    console.error('Error generating device verifier:', error);
                    throw error;
                }
            } // Function ends

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
                    let deviceData = localStorage.getItem(this.secureCode + this.userkeyB64encoded);
                    if (!deviceData) {
                        throw new Error('No passkey data found for the device in local storage');
                    }
                    deviceData = JSON.parse(deviceData);

                    // Get Base64 encoded Secret Block
                    let objChallengeParams = this.ChallengeParams;
                    let secretBlock = objChallengeParams?.SECRET_BLOCK || null;
                    let secretBlockBase64 = secretBlock ? atob(secretBlock) : null;
                    if (!secretBlockBase64) {
                        throw new Error("Secret block not found in challenge parameters");
                    }

                    let deviceGroupKey = deviceData['d-grp'];
                    if (!deviceGroupKey) {
                        throw new Error("Device group key not found in localStorage");
                    }

                    // Get the device key
                    let deviceKey = objChallengeParams?.DEVICE_KEY || deviceData['d-key'];
                    if (!deviceKey) {
                        throw new Error("Device key not found in challenge parameters");
                    }

                    //Build the message
                    let message = '';                        
                    message += deviceGroupKey + deviceKey;
                    message += secretBlockBase64;
                    message += this.CognitoTimestamp;

                    return btoa(message);
                } catch (error) {
                    console.error('Error computing message verifier:', error);
                    throw error;
                }
            } // Function ends
        } // Class ends
    </script>
@endPushIf
