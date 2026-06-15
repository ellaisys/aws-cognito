@props([
    'includeCryptoJS' => true,
    'includeCryptoUtils' => true,
])

@pushIf(($includeCryptoJS || $includeCryptoUtils), 'cognito-common-crypto-scripts')
    @if ($includeCryptoJS)
        <!-- CryptoJS for client-side cryptographic operations -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"
            integrity="sha512-a+SUDuwNzXDvz4XrIcXHuCf089/iJAoN4lmrXJg18XnduKK6YlDHNRalv4yd1N40OKI80tFidF+rqTFKGPoWFQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer">
        </script>
    @endif
    
    @if ($includeCryptoUtils)
        <!-- Custom CryptoUtils class for cryptographic operations -->
        <script>
            /**
             * Utility class to handle cryptographic operations
             * in JavaScript using the CryptJS library.
             */
            class CryptoUtils {
                // Large prime number
                static N_BigInt = BigInt("{{ '0x' . config('cognito.srp_parameters.N_HEX') }}");
                
                // Generator value
                static g_BigInt = BigInt("{{ '0x' . config('cognito.srp_parameters.G_HEX') }}");

                static randomBytes(length) {
                    // Check if CryptoJS is available
                    if (this.isCryptoJSInstalled()) {
                        return CryptoJS.lib.WordArray.random(length);
                    }
                    const randomValues = new Uint8Array(length);

                    // Populates the array with secure random numbers
                    crypto.getRandomValues(randomValues);
                    return randomValues;
                } // Function ends

                static randomHex(length = 16) {
                    const bytes = this.randomBytes(length);

                    // Check if CryptoJS is available
                    if (this.isCryptoJSInstalled() && this.#isCryptoJSWordArray(bytes)) {
                        return bytes.toString(CryptoJS.enc.Hex).toUpperCase();
                    } else if (bytes instanceof Uint8Array) {
                        let hex = Array.from(bytes)
                            .map(b => b.toString(16).padStart(2, '0'))
                            .join('');

                        // Prevent negative BigInteger interpretation
                        if (bytes.length > 0 && (bytes[0] & 0x80)) {
                            hex = "00" + hex;
                        }

                        return hex.toUpperCase();
                    } else {
                        throw new TypeError("Expected a CryptoJS WordArray or Uint8Array.");
                    } // End if
                } // Function ends

                static randomBase64(length = 16) {
                    const bytes = this.randomBytes(length);

                    // Check if CryptoJS is available
                    if (this.isCryptoJSInstalled() && this.#isCryptoJSWordArray(bytes)) {
                        return CryptoJS.enc.Base64.stringify(bytes);
                    } else if (bytes instanceof Uint8Array) {
                        return btoa(String.fromCharCode(...bytes));
                    } else {
                        throw new TypeError("Expected a CryptoJS WordArray or Uint8Array.");
                    } // End if
                } // Function ends

                static convHexToBigInt(hex) {
                    // Remove leading zeros
                    hex = hex.replace(/^0+/, '');
                    return BigInt("0x" + hex);
                } // Function ends

                static convBigIntToHex(bigInt) {
                    let hex = bigInt.toString(16);

                    // Ensure even length for proper byte representation
                    if (hex.length % 2) {
                        hex = "0" + hex;
                    }
                    return hex.toUpperCase();
                } // Function ends

                static convBigIntToUnsignedHex(bigInt) {
                    let hex = this.convBigIntToHex(bigInt);

                    // if high bit set => prepend 00
                    // prevents negative BigInteger interpretation
                    const firstByte = parseInt(hex.slice(0, 2), 16);
                    if (firstByte & 0x80) {
                        hex = "00" + hex;
                    }

                    return hex.toUpperCase();
                } // Function ends

                /**
                 * Converts a hex string to a Base64 string.
                 *
                 * @param {string} hex - The hex string to be converted
                 * @returns {string} - The resulting Base64 string
                 **/
                static convHexToBase64(hex) {
                    // Check if CryptoJS is available
                    if (this.isCryptoJSInstalled()) {
                        return CryptoJS.enc.Hex.parse(hex)
                            .toString(CryptoJS.enc.Base64);
                    } // End if

                    const bytes = new Uint8Array(hex.length / 2);

                    for (let i = 0; i < bytes.length; i++) {
                        bytes[i] = parseInt(hex.substr(i * 2, 2), 16);
                    }

                    return btoa(String.fromCharCode(...bytes));
                } // Function ends

                static convHexToUnsignedHex(hex) {
                    // if high bit set => prepend 00
                    // prevents negative BigInteger interpretation
                    const firstByte = parseInt(hex.slice(0, 2), 16);
                    if (firstByte & 0x80) {
                        hex = "00" + hex;
                    }

                    return hex.toUpperCase();
                } // Function ends

                /**
                 * Hashes a hex string using SHA-256.
                 *
                 * @param {string} hex - The hex string to be hashed
                 * @returns {Promise<string>} - A promise that resolves to the hex string of the hash
                 **/
                static async hexHash(hex) {
                    let bytes;
                    // Check if CryptoJS is available
                    if (this.isCryptoJSInstalled()) {
                        bytes = CryptoJS.enc.Hex.parse(hex);
                    } else {
                        bytes = new Uint8Array(hex.length / 2);

                        for (let i = 0; i < bytes.length; i++) {
                            bytes[i] = parseInt(hex.substr(i * 2, 2), 16);
                        }
                    } // End if

                    return await this.hashEncrypt(bytes);
                } // Function ends

                /**
                 * Utility function to hash a value using the Web Crypto API
                 *
                 * @param {string} value - The value to be hashed
                 * @param {string} key - The hashing algorithm (default is 'SHA-256')
                 *
                 * @returns {Promise<string>} - A promise that resolves to the hex string of the hash
                 **/
                static async hashEncrypt(value, key = "SHA-256")
                {
                    // Check if CryptoJS is available
                    if (this.isCryptoJSInstalled() && key === "SHA-256") {
                        if (typeof value === "string") {
                            value = CryptoJS.enc.Utf8.parse(value);
                        } else if (value instanceof Uint8Array) {
                            value = CryptoJS.lib.WordArray.create(value);
                        } else if (this.#isCryptoJSWordArray(value)) {
                            // Already in the correct format, do nothing
                        } else {
                            throw new TypeError("Improper data type for hashing. Expected a string, Uint8Array, or CryptoJS WordArray.");
                        }
                        return CryptoJS.SHA256(value).toString(CryptoJS.enc.Hex);
                    } // End if

                    let bytes;
                    if (typeof value === "string") {
                        let encoder = new TextEncoder();
                        bytes = encoder.encode(value);
                    } else if (value instanceof Uint8Array) {
                        bytes = value;
                    } else {
                        throw new TypeError("Expected a string or Uint8Array.");
                    }

                    // Hash the data using the specified key (e.g., SHA-256)
                    let hashBuffer = await crypto.subtle.digest(key, bytes);

                    // Convert the hash buffer to a hex string
                    return Array.from(new Uint8Array(hashBuffer))
                        .map(b => b.toString(16).padStart(2, '0'))
                        .join('');
                } // Function ends

                static isCryptoJSInstalled () {
                    return typeof CryptoJS !== 'undefined';
                } // Function ends

                static #isCryptoJSWordArray(value) {
                    return value &&
                        typeof value === "object" &&
                        Array.isArray(value.words) &&
                        typeof value.sigBytes === "number";
                } // Function ends
            } // Class ends
        </script>
    @endif
@endPushIf
