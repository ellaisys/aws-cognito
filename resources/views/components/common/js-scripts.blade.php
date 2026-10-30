@push('cognito-common-scripts')
    <script>
        class CognitoAlert {
            constructor(message = null) {
                // Initialize any properties if needed
                if (message) {
                    this.info(message);
                }
            }

            success(message) {
                this.#alertbox('Success', message, 'success');
            }

            error(message) {
                this.#alertbox('Error', message, 'error');
            }

            info(message) {
                this.#alertbox('Info', message, 'info');
            }

            processing(message = 'Processing...') {
                return Swal.fire({
                    title: message,
                    text: 'Please wait while we process your request.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            #alertbox(title, text, icon = 'success', timer = 3000,
                showConfirmButton = false, allowOutsideClick = true,
                allowEscapeKey = true)
            {
                try {
                    return Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        confirmButtonText: 'Cool',
                        allowOutsideClick: allowOutsideClick,
                        allowEscapeKey: allowEscapeKey,
                        showConfirmButton: showConfirmButton,
                        timer: timer
                    });
                } catch (error) {
                    console.error('Error showing alert:', error);
                    // Fallback to default alert if SweetAlert2 fails
                    alert(`${title}: ${text}`);
                }
            }
        } // Class ends
    </script>
@endpush
