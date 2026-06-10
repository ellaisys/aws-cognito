@pushif((config('cognito.allow_passkeys')),'cognito-passkey-webauthn-scripts')
    <script>
        const enablePasskeysButton = document.getElementById('enable-passkeys-button');

        enablePasskeysButton.addEventListener('click', function() {
            let webAuthn = new WebAuthnRegistration();
            webAuthn.register();
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
                    let completePayload = await this.#completeRegistration(credential);
                    if (completePayload) {
                        this.#alert('Passkey registered successfully.', 'success');
                    }
                } catch (error) {
                    console.error('Error registering passkey:', error);
                    this.#alert('Passkey registration failed. Check the console for details.', 'error');
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
            async #completeRegistration(credential) {
                try {
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
                    }

                    return await completeResponse.json();
                } catch (error) {
                    console.error('Error completing WebAuthn registration:', error);
                    throw error;
                }
            } //Function end

            async deleteRegistration(credentialId, rpId) {
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

                    await PublicKeyCredential.signalUnknownCredential({
                        rpId: rpId,           // The ID of the Relying Party
                        credentialId: btoa(credentialId)   // The unrecognized credential ID
                    });
                } catch (error) {
                    console.error('Error deleting passkey:', error);
                    this.#alert('Passkey deletion failed. Check the console for details.', 'error');
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
    </script>
@endPushIf
