@push('cognito-common-scripts')
    <script>
        /**
         * Utility class to handle Multiple Precision BigInt operations
         * in JavaScript.
         */
        class GMP {
            /**
             * Utility function to perform modular exponentiation (base^exponent mod modulus)
             * This function is essential for the SRP protocol calculations, where we need
             * to compute values like A = g^a mod N.
             * @param {BigInt} base - The base value (e.g., g in SRP)
             * @param {BigInt} exponent - The exponent value (e.g., a in SRP)
             * @param {BigInt} modulus - The modulus value (e.g., N in SRP)
             * @returns {BigInt} - The result of (base^exponent) mod modulus
             **/
            static gmp_powm(base, exponent, modulus) {
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
                } // End while

                return result;
            } // Function ends

            /** 
             * Utility function to perform addition of two BigInt values. This is a simple
             * wrapper around the native BigInt addition operator, but it can be extended
             * in the future to include additional checks or functionality if needed.
             * @param {BigInt} a - The first BigInt value
             * @param {BigInt} b - The second BigInt value
             * @returns {BigInt} - The result of a + b
             **/
            static gmp_add(num1, num2) {
                return this.#toBigInt(num1) + this.#toBigInt(num2);
            } // Function ends

            static gmp_sub(num1, num2) {
                return this.#toBigInt(num1) - this.#toBigInt(num2);
            } // Function ends

            static gmp_mul(num1, num2) {
                return this.#toBigInt(num1) * this.#toBigInt(num2);
            } // Function ends

            static gmp_mod(num1, num2) {
                return this.#toBigInt(num1) % this.#toBigInt(num2);
            } // Function ends

            static gmp_init(value, base = 10) {
                return this.#toBigInt(value, base);
            } // Function ends

            static #toBigInt(value, base = null)
            {
                // Already a BigInt
                if (typeof value === 'bigint') {
                    return value;
                }

                // Number
                if (typeof value === 'number') {
                    return BigInt(value);
                }

                // String
                if (typeof value === 'string') {
                    value = value.trim();

                    // Explicit base supplied (similar to gmp_init)
                    if (base === 16) {
                        return BigInt('0x' + value.replace(/^0x/i, ''));
                    }

                    if (base === 2) {
                        return BigInt('0b' + value.replace(/^0b/i, ''));
                    }

                    // Auto-detect prefixes
                    if (/^0x[0-9a-f]+$/i.test(value)) {
                        return BigInt(value);
                    }

                    if (/^0b[01]+$/i.test(value)) {
                        return BigInt(value);
                    }

                    // Decimal
                    if (/^[+-]?\d+$/.test(value)) {
                        return BigInt(value);
                    }

                    throw new Error(`Invalid numeric string: ${value}`);
                }

                throw new TypeError(`Unsupported type: ${typeof value}`);
            }

        } // Class ends

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

            #alertbox(title, text, icon = 'success', timer = 3000, showConfirmButton = false) {
                try {
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: icon,
                        confirmButtonText: 'Cool',
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
