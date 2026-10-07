@push('cognito-mfa-activate-form')
    <div class="modal fade" id="modalMfaActivate" tabindex="-1"
        aria-labelledby="modalMfaActivateLabel" aria-hidden="true">
        <div class="card-body modal-dialog" id="modalMfaActivateContent">
        </div>
    </div>
@endpush

@push('cognito-mfa-scripts')
    <script>
        /**
         * Event listener for DOMContentLoaded to trigger the appropriate
         * actions.
         */
        document.addEventListener("DOMContentLoaded", function(event) {
            // Add event listeners to all buttons with the data-role attribute set to "mfa"
            const elemsDeviceAuth = document.querySelectorAll('[data-role="mfa"]');
            elemsDeviceAuth.forEach(button => {
                try {
                    // Initialize and check device registration status
                    let mfaService = new MfaService();

                    // Element action attribute to determine the action to be performed
                    let dataAction = button?.attributes['data-action']?.value?.toLowerCase() ?? null;
                    if (dataAction === 'activate' && mfaService) {
                        button.disabled = false;
                    }

                    if (dataAction === 'deactivate' && mfaService) {
                        button.disabled = false;
                    }
                } catch (error) {
                    console.error('Error processing device auth button:', error);
                    button.disabled = false;
                } // Try ends

                button.addEventListener('click', async function() {
                    // Disable the button to prevent multiple clicks
                    this.disabled = true;

                    // Get the action from the data-action attribute and validate it
                    let dataAction = button?.attributes['data-action']?.value?.toLowerCase() ?? null;
                    if (!dataAction) {
                        console.warn('No action specified for MFA button.');
                        this.disabled = false;
                        return;
                    } //End if

                    const service = new MfaService();
                    if (dataAction === 'activate') { // Activate MFA
                        let response = await service.activate();
                        this.disabled = false;
                    } else if (dataAction === 'deactivate') { // Deactivate MFA
                        let response = await service.deactivate();
                        this.disabled = false;
                    } else { // Handle unknown action
                        console.warn('Unknown action for MFA button.');

                        // Re-enable the button if action is unknown
                        this.disabled = false;
                    } //End if
                });
            });
        });

        /**
         * Class to handle the Mfa related actions. It communicates with
         * the server to obtain the service details.
         */
        class MfaService {

            /**
             * Constructor to initialize the MfaService class
             * with necessary parameters for secure communication with
             * the server.
             */
            constructor() {
                this.csrfToken = "{{ csrf_token() }}";
                this.isAdmin = "{{ $isAdminRole ?: false }}";
            }

            /**
             * Main function to activate the MFA for the user. It
             * orchestrates the entire activation process by
             * communicating with the server and the client.
             */
            async activate() {
                try {
                    // Get the Mfa activation options from the server
                    let response = await this.#activate();

                    // On successful response from the server
                    if (response) {
                        console.log('MFA activation response');

                        // Add the response as inner html to the modal content
                        const modalContent = document.getElementById('modalMfaActivateContent');
                        if (modalContent) {
                            modalContent.innerHTML = response;
                        } //End if

                        // Show the action popup
                        const modalId = document.getElementById('modalMfaActivate');
                        if (Modal && modalId) {
                            Modal.getOrCreateInstance(modalId).show();
                        } //End if
                        
                        return true;
                    } //End if

                    return false;
                } catch (error) {
                    console.error('Error activating MFA:', error);
                    this.#alert('MFA activation failed. Check the console for details.', 'error');
                    return false;
                } //Try-catch ends
            } //Function end

            /**
             * Main function to deactivate the MFA for the user. It
             * orchestrates the entire deactivation process by
             * communicating with the server and the client.
             */
            async deactivate() {
                try {
                    // Get the device deactivation options from the server
                    let response = await this.#deactivate();

                    // On successful response from the server
                    if (response && response?.data && response.status === 'success') {
                        this.#alert('MFA deactivated successfully.', 'success');
                        return true;
                    } //End if

                    return false;
                } catch (error) {
                    console.error('Error deactivating MFA:', error);
                    this.#alert('MFA deactivation failed. Check the console for details.', 'error');
                    return false;
                } //Try-catch ends
            } //Function end

            /**
             * Function to activate the MFA for the user. It communicates
             * with the server to initiate the activation process.
             */
            async #activate() {
                try {
                    // Show processing alert
                    window.processingAlert = this.#alert('Activating MFA...', 'processing');

                    // Communicate with the server to activate MFA
                    let response = await fetch("{{ $urlMfaActivateEndpoint ?? '' }}", {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'text/html',
                            'Accept': 'text/html',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    });

                    // Hide processing alert once response is received
                    if (window.processingAlert) {
                        Swal.close();
                        window.processingAlert = null;
                    } //End if

                    if (!response.ok) {
                        throw new Error('Failed to activate MFA');
                    } //End if

                    // Create a text decoder for the incoming byte stream
                    let responseValue = '';
                    const decoder = new TextDecoder("utf-8");

                    // Iterate over each chunk of the HTML stream as it arrives
                    for await (const chunk of response.body) {
                        const htmlTextChunk = decoder.decode(chunk, { stream: true });

                        // Process the chunk (e.g., append it to the DOM)
                        responseValue += htmlTextChunk;
                    } //Loop ends

                    return await responseValue;
                } catch (error) {
                    console.error('Error activating MFA:', error);
                    throw error;
                } //Try-catch ends
            } //Function end

            /**
             * Function to deactivate the MFA for the user. It communicates
             * with the server to initiate the deactivation process.
             */
            async #deactivate() {
                try {
                    // Show processing alert
                    window.processingAlert = this.#alert('Deactivating MFA...', 'processing');

                    // Communicate with the server to deactivate MFA
                    let response = await fetch("{{ $urlMfaDeactivateEndpoint ?? '' }}", {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    });

                    // Hide processing alert once response is received
                    if (window.processingAlert) {
                        Swal.close();
                        window.processingAlert = null;
                    } //End if

                    if (!response.ok) {
                        throw new Error('Failed to deactivate MFA');
                    } //End if

                    return await response.json();
                } catch (error) {
                    console.error('Error deactivating MFA:', error);
                    throw error;
                } //Try-catch ends
            } //Function end

            async #enable() {
                try {
                    // Show processing alert
                    window.processingAlert = this.#alert('Enabling MFA...', 'processing');

                    // Communicate with the server to enable MFA
                    let response = await fetch("{{ $urlMfaEnableEndpoint ?? '' }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    });

                    // Hide processing alert once response is received
                    if (window.processingAlert) {
                        Swal.close();
                        window.processingAlert = null;
                    } //End if

                    if (!response.ok) {
                        throw new Error('Failed to enable MFA');
                    } //End if

                    return await response.json();
                } catch (error) {
                    console.error('Error enabling MFA:', error);
                    throw error;
                } //Try-catch ends
            } //Function end

            async #disable() {
                try {
                    // Show processing alert
                    window.processingAlert = this.#alert('Disabling MFA...', 'processing');

                    // Communicate with the server to disable MFA
                    let response = await fetch("{{ $urlMfaDisableEndpoint ?? '' }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    });

                    // Hide processing alert once response is received
                    if (window.processingAlert) {
                        Swal.close();
                        window.processingAlert = null;
                    } //End if

                    if (!response.ok) {
                        throw new Error('Failed to disable MFA');
                    } //End if

                    return await response.json();
                } catch (error) {
                    console.error('Error disabling MFA:', error);
                    throw error;
                } //Try-catch ends
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
                    } else if (type === 'processing') {
                        return alertBox.processing(message);
                    } else {
                        alertBox.info(message);
                    }
                } else {
                    // Fallback to default alert if CognitoAlert is not available
                    alert(message);
                } //End if
            } //Function end

        } //Class end
    </script>
@endpush
