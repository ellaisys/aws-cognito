@props([
    'challengeNameValue' => 'NONE'
])

@pushif((config('cognito.allow_passkeys')),'cognito-passkey-webauthn-scripts')
    <script>
        // Add event listeners to all buttons with the data-role attribute set to "passkey-webauthn"
        const elemsPasskeyWebAuthn = document.querySelectorAll('[data-role="passkey-webauthn"]');
        elemsPasskeyWebAuthn.forEach(button => {
            //Check the data-action and enable the button
            let btnDataAction = button.attributes['data-action'] ? (button.attributes['data-action'].value).toLowerCase() : null;
            let isPasskeyEnabled = {{ $passkeyEnabled ? 'true' : 'false' }};
            if (btnDataAction && (btnDataAction === 'register')) {
                button.disabled = isPasskeyEnabled; // Enable the register button only if passkeys are not enabled
            } else if (btnDataAction && (btnDataAction === 'delete')) {
                button.disabled = !isPasskeyEnabled; // Enable the delete button only if passkeys are enabled
            } else {
                return;
            } //End if

            button.addEventListener('click', async function() {
                // Disable the button to prevent multiple clicks
                this.disabled = true;

                // Get the action from the data-action attribute and validate it
                let dataAction = this.attributes['data-action'] ? (this.attributes['data-action'].value).toLowerCase() : null;
                if (!dataAction || (dataAction !== 'register' && dataAction !== 'delete')) {
                    console.warn('No action specified for passkey button. Use "register" or "delete" as data-action value.');
                    this.disabled = false;
                    return;
                } //End if

                if (dataAction === 'register') { // Register a new passkey
                    let webAuthn = new WebAuthnRegistration();
                    let response = await webAuthn.register();

                    // Disable on success, re-enable on failure
                    this.disabled = response;
                } else if (dataAction === 'delete') { // Delete an existing passkey
                    // Get the user key from the data attribute
                    let webAuthn = new WebAuthnRegistration();
                    await webAuthn.delete();

                    // Re-enable the button after deletion
                    this.disabled = false;
                } else { // Handle unknown action
                    console.warn('Unknown action for passkey button. Use "register" or "delete" as data-action value.');

                    // Re-enable the button if action is unknown
                    this.disabled = false;
                } //End if
            });
        });

        /**
         * Class to handle WebAuthn registration and deletion for passkeys.
         * It includes methods to start the registration process, complete it,
         * and delete existing passkeys.
         */
        class WebAuthnRegistration {

            /**
             * Constructor to initialize the WebAuthnRegistration class
             * with necessary parameters for secure communication with
             * the server during the passkey registration process.
             */
            constructor() {
                this.csrfToken = "{{ csrf_token() }}";
                this.secureCode = "{{ $secureCode ?? '' }}";
            }

            /**
             * Main function to register passkeys for the user. It orchestrates
             * the entire registration process by communicating with the
             * server and using the WebAuthn API.
             */
            async register() {
                try {
                    // Get the passkey registration options from the server
                    let startPayload = await this.#startRegistration();

                    // Convert the server response to the format required for FIDO2 registration
                    let publicKeyOptions = this.#getPublicKeyCreationOptions(startPayload);

                    // Complete the passkey registration using the WebAuthn API
                    let credential = await navigator.credentials.create({
                        publicKey: publicKeyOptions
                    });

                    // Save the created credential back to the server to complete registration
                    let completePayload = await this.#completeRegistration(credential, publicKeyOptions);
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
             * Function to delete an existing passkey for the user. It communicates
             * with the server to delete the passkey and signals the authenticator
             * about the deleted credential.
             */
            async delete() {
                try {
                    // Read the data to local store
                    let userData = localStorage.getItem(this.secureCode);
                    if (!userData) {
                        throw new Error('No passkey data found for the user in local storage');
                    }
                    userData = JSON.parse(atob(userData));

                    // Signal the authenticator about the deleted credential
                    let deletePayload = await this.#deleteRegistration(userData?.credential_id, userData?.rp_id);
                    if(deletePayload) {
                        // Remove the data from local store
                        localStorage.removeItem(this.secureCode);

                        this.#alert('Passkey deleted successfully.', 'success');
                        return true;
                    } //End if

                    return false;
                } catch (error) {
                    console.error('Error deleting passkey data:', error);
                    this.#alert('Passkey deletion failed. Check the console for details.', 'error');
                    return false;
                }
            } //Function end

            /**
             * Function to start the passkey registration process by
             * requesting options from the server
             */
            async #startRegistration() {
                try {
                    // Get the passkey registration options from the server
                    let startResponse = await fetch("{{ $urlPasskeyStartEndpoint ?? '' }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    });

                    if (!startResponse.ok) {
                        throw new Error(startResponse.statusText || 'Failed to start passkey registration');
                    }

                    return await startResponse.json();
                } catch (error) {
                    console.error('Error with starting WebAuthn registration:', error);
                    throw error;
                }
            } //Function end

            /**
             * Function to complete the passkey registration process by
             * sending the created credential back to the server
             */
            async #completeRegistration(credential, publicKeyOptions) {
                try {
                    if (!credential && (typeof credential !== 'object') && (credential?.type !== 'public-key')) {
                        throw new Error('No credential created by WebAuthn API');
                    } //End if

                    // Save the created credential back to the server to complete registration
                    let completeResponse = await fetch("{{ $urlPasskeyCompleteEndpoint ?? '' }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            credential: this.#credentialToCognitoPayload(credential)
                        })
                    });

                    if (!completeResponse.ok) {
                        throw new Error('Failed to complete passkey registration');
                    } //End if

                    // Save the data to local store
                    let userData = {
                            credential_id: credential?.id,
                            rp_id: publicKeyOptions?.rp?.id,
                            user_name: publicKeyOptions?.user?.name
                        };
                    userData = btoa(JSON.stringify(userData));
                    localStorage.setItem(this.secureCode, userData);

                    return await completeResponse.json();
                } catch (error) {
                    console.error('Error completing WebAuthn registration:', error);
                    throw error;
                }
            } //Function end

            async #deleteRegistration(credentialId, rpId) {
                try {
                    // Get the passkey registration options from the server
                    let deleteResponse = await fetch("{{ $urlPasskeyDeleteEndpoint ?? '' }}", {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        },
                        body: JSON.stringify({
                            credential_id: credentialId
                        })
                    });

                    if (!deleteResponse.ok) {
                        throw new Error('Failed to delete passkey');
                    }

                    // Signal the authenticator about the deleted credential
                    if (PublicKeyCredential.signalUnknownCredential) {
                        await PublicKeyCredential.signalUnknownCredential({
                            rpId: rpId,
                            credentialId: credentialId
                        });
                    } else {
                        this.#alert('PublicKeyCredential.signalUnknownCredential is not supported in this browser. Please delete the credential manually.', 'info');
                    } //End if

                    return await deleteResponse.json();
                } catch (error) {
                    console.error('Error deleting passkey:', error);
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

        @if ($challengeNameValue === 'WEB_AUTHN')
        /**
         * Class to handle the WebAuthn authentication challenge. It retrieves the
         * challenge parameters from the server, prompts the user to authenticate
         * using their passkey credential, and submits the authentication response
         * back to the server.
         */
        class WebAuthnChallenge extends CognitoChallenge {
            // Default constructor
            constructor() {
                super();
            }

            /**
             * Function to handle the WebAuthn challenge authentication process.
             * It retrieves the challenge parameters from the server, prompts
             * the user to authenticate using their passkey credential, and
             * submits the authentication response back to the server.
             * @returns {Promise<string>} - A promise that resolves to
             * the WebAuthn challenge verification
             * @throws {Error} - Throws an error during the process.
             **/
            async verifier() {
                try {
                    // Get the challenge parameters value and parse it as JSON
                    let objChallengeParams = this.ChallengeParams;

                    /**
                     * Build the options for navigator.credentials.get() based
                     * on the challenge parameters received from the server
                     */
                    let signinOptions = JSON.parse(objChallengeParams.CREDENTIAL_REQUEST_OPTIONS);
                    signinOptions.challenge = Uint8Array.from(
                        atob(signinOptions.challenge.replace(/-/g, '+')
                            .replace(/_/g, '/')), c => c.charCodeAt(0)
                        );
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
                    if (!credential) {
                        throw new Error("No credential returned from navigator.credentials.get()");
                    } // End if

                    return JSON.stringify(credential);
                } catch (error) {
                    console.error('Error authenticating passkey:', error);
                    throw error;
                } // End try-catch
            } // Function ends
        } // Class ends
        @endif
    </script>
@endPushIf
