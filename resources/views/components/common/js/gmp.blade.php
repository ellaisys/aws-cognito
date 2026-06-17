@props([
    'includeGMP' => true
])

@pushIf($includeGMP, 'cognito-common-gmp-scripts')
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
                if (typeof base !== "bigint") {
                    throw new TypeError("base must be a BigInt");
                }

                if (typeof exponent !== "bigint") {
                    throw new TypeError("exponent must be a BigInt");
                }

                if (typeof modulus !== "bigint") {
                    throw new TypeError("modulus must be a BigInt");
                }

                if (modulus <= 0n) {
                    throw new RangeError("modulus must be greater than zero");
                }

                if (exponent < 0n) {
                    throw new RangeError("exponent must be non-negative");
                }

                if (modulus === 1n) {
                    return 0n;
                }

                let result = 1n;

                // Normalize the base to the range [0, modulus - 1]
                let currentBase = ((base % modulus) + modulus) % modulus;
                let currentExponent = exponent;

                while (currentExponent > 0n) {
                    if ((currentExponent & 1n) === 1n) {
                        result = (result * currentBase) % modulus;
                    }

                    currentExponent >>= 1n;
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

            /**
             * Utility function to perform subtraction of two BigInt values. This is a simple
             * wrapper around the native BigInt subtraction operator, but it can be extended
             * in the future to include additional checks or functionality if needed.
             * @param {BigInt} a - The first BigInt value
             * @param {BigInt} b - The second BigInt value
             * @returns {BigInt} - The result of a - b
             **/
            static gmp_sub(num1, num2) {
                return this.#toBigInt(num1) - this.#toBigInt(num2);
            } // Function ends

            /**
             * Utility function to perform multiplication of two BigInt values. This is a simple
             * wrapper around the native BigInt multiplication operator, but it can be extended
             * in the future to include additional checks or functionality if needed.
             * @param {BigInt} a - The first BigInt value
             * @param {BigInt} b - The second BigInt value
             * @returns {BigInt} - The result of a * b
             **/
            static gmp_mul(num1, num2) {
                return this.#toBigInt(num1) * this.#toBigInt(num2);
            } // Function ends

            static gmp_mod(num1, num2) {
                return this.#toBigInt(num1) % this.#toBigInt(num2);
            } // Function ends

            /**
             * Utility function to initialize a BigInt value from various input types. This function
             * can handle strings (in decimal, hexadecimal, or binary format), numbers, and already
             * existing BigInt values. It also supports an optional base parameter for string inputs.
             * @param {string|number|BigInt} value - The input value to be converted to BigInt
             * @param {number} [base=10] - The base of the input string (10 for decimal, 16 for hex, 2 for binary)
             * @returns {BigInt} - The initialized BigInt value
             **/
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
    </script>
@endPushIf
