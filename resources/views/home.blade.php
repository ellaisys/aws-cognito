@extends(config('cognito.views.layout'))

@section('content')

@php
    $passkeyEnabled = (Auth::user() && isset(Auth::user()->is_webauthn_enabled)) ? Auth::user()->is_webauthn_enabled : false;
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-2">
                <div class="card-header">
                    {{ __('Dashboard') }}
                    @if (config('cognito.allow_passkeys') && !$passkeyEnabled)
                    <button id="enable-passkeys-button" class="btn btn-outline-primary float-end">Enable Passkeys</button>
                    @endif
                </div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    
                    <img src="https://github.com/ellaisys/aws-cognito/raw/master/assets/images/banner.png"
                        width="100%" alt="EllaiSys AWS Cloud Capability"/>

                    <h2><strong>Welcome: {{ __('You are logged in!') }}</strong></h2>
                    <h4>This is a demo application, that uses the Laravel Package to manage Web and API authentication with AWS Cognito</h4>

                    </br>
                    <h2><strong>Session Parameters:</strong></h2>
                    @if ($sessionData = session()->all())
                        <table class="table table-bordered table-striped">
                                <thead class="dark">
                                    <tr>
                                        <td style="width: 30%;">Key</td>
                                        <td>Value</td>
                                    </tr>
                                </thead>
                            <tbody>
                            @foreach($sessionData as $key=>$value)
                                <tr>
                                    <td style="word-break: break-word;">{{ $key }}</td>
                                    <td style="word-break: break-word;">{{ json_encode($value, JSON_UNESCAPED_UNICODE)}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <x-cognito::mfa.activate-form />
        </div>
    </div>
</div>

@if (config('cognito.allow_passkeys'))
<script>
    const urlPasskeyStartEndpoint = "{{Route::has('cognito.action.user.passkey.start') ? (route('cognito.action.user.passkey.start')) : 'null'}}";
    const urlPasskeyCompleteEndpoint = "{{Route::has('cognito.action.user.passkey.complete') ? (route('cognito.action.user.passkey.complete')) : 'null'}}";
    const urlPasskeyDeleteEndpoint = "{{Route::has('cognito.action.user.passkey.delete') ? (route('cognito.action.user.passkey.delete')) : 'null'}}";
    const CSRF_TOKEN = '{{ csrf_token() }}';

    const enablePasskeysButton = document.getElementById('enable-passkeys-button');
    enablePasskeysButton.addEventListener('click', function() {
        enablePasskeys();
    });

    // Function to handle the passkey registration process
    async function enablePasskeys() {
        try {
            // Get the passkey registration options from the server
            var startResponse = await fetch(urlPasskeyStartEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                }
            });

            if (!startResponse.ok) {
                throw new Error('Failed to start passkey registration');
            }

            // Convert the server response to the format required for FIDO2 registration
            var startPayload = await startResponse.json();
            var publicKeyOptions = getPublicKeyCreationOptions(startPayload);

            // Create the passkey credential using the WebAuthn API
            var credential = await navigator.credentials.create({
                publicKey: publicKeyOptions
            });

            // Save the created credential back to the server to complete registration
            var completeResponse = await fetch(urlPasskeyCompleteEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    credential: credentialToCognitoPayload(credential)
                })
            });

            if (!completeResponse.ok) {
                throw new Error('Failed to complete passkey registration');
            }

            var completePayload = await completeResponse.json();
            this.successAlert('Passkey registered successfully.');
        } catch (error) {
            console.error('Error enabling passkey:', error);
            this.errorAlert('Passkey registration failed. Check the console for details.');
        }
    }

    /**
     *  Function to convert the server response into the format required
     *  for WebAuthn registration
     */
    function getPublicKeyCreationOptions(startPayload) {
        var rawOptions = startPayload && startPayload.data
            ? startPayload.data.CredentialCreationOptions || startPayload.data.credentialCreationOptions || startPayload.data
            : null;

        if (!rawOptions) {
            throw new Error('CredentialCreationOptions not found in start response');
        }

        if (typeof rawOptions === 'string') {
            rawOptions = JSON.parse(rawOptions);
        }

        var publicKeyOptions = rawOptions.publicKey ? rawOptions.publicKey : rawOptions;
        publicKeyOptions.challenge = base64urlToUint8Array(publicKeyOptions.challenge);

        if (publicKeyOptions.user && publicKeyOptions.user.id) {
            publicKeyOptions.user.id = base64urlToUint8Array(publicKeyOptions.user.id);
        }

        if (Array.isArray(publicKeyOptions.excludeCredentials)) {
            publicKeyOptions.excludeCredentials = publicKeyOptions.excludeCredentials.map(function (credentialDescriptor) {
                return Object.assign({}, credentialDescriptor, {
                    id: base64urlToUint8Array(credentialDescriptor.id)
                });
            });
        }

        return publicKeyOptions;
    }

    /**
     *  Utility functions for base64url encoding/decoding and converting
     *  credentials to a format suitable for sending to the server
     */
    function base64urlToUint8Array(base64url) {
        var padding = '='.repeat((4 - (base64url.length % 4)) % 4);
        var base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
        const binaryString = window.atob(base64);
        return Uint8Array.from(binaryString, c => c.charCodeAt(0));
    }

    /**
     *  Convert the credential object returned by the WebAuthn API into a format
     *  that can be sent to the server for registration completion
     */
    function bufferToBase64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var binary = '';
        bytes.forEach(function (byte) {
            binary += String.fromCharCode(byte);
        });

        return window.btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/g, '');
    }

    /**
     *  Convert the credential object returned by the WebAuthn API into a format
     *  that can be sent to the server for registration completion
     */
    function credentialToCognitoPayload(credential) {
        return JSON.stringify({
            id: credential.id,
            type: credential.type,
            rawId: bufferToBase64url(credential.rawId),
            authenticatorAttachment: credential.authenticatorAttachment,
            response: {
                clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                attestationObject: bufferToBase64url(credential.response.attestationObject),
                transports: typeof credential.response.getTransports === 'function'
                    ? credential.response.getTransports()
                    : []
            },
            clientExtensionResults: credential.getClientExtensionResults()
        }, null, 2);
    }
    
    async function deletePasskey(credentialId, rpId) {
        try {
            // Get the passkey registration options from the server
            var deleteResponse = await fetch(urlPasskeyDeleteEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
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
            this.errorAlert('Passkey deletion failed. Check the console for details.');
        }
    }
</script>
@endif

<script>
    function successAlert(message) {
        this.alertbox('Success', message, 'success');
    }

    function errorAlert(message) {
        this.alertbox('Error', message, 'error');
    }

    function alertbox(title, text, icon = 'success', timer = 3000, showConfirmButton = false) {
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
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
<script>
    const CryptoJS = window.CryptoJS;
    
    const btnCreateDevice = document.getElementById('create-device');
    const btnDeleteDevice = document.getElementById('delete-device');

    const claim = @json(session('claim', []));
    const dMeta = claim?.data?.NewDeviceMetadata;

    if (dMeta && localStorage.getItem('d-secret') === null) {
        localStorage.setItem('d-key', dMeta.DeviceKey);
        localStorage.setItem('d-grp', dMeta.DeviceGroupKey);

        btnCreateDevice.removeAttribute('disabled');
        btnDeleteDevice.setAttribute('disabled', 'disabled');
    } else if (dMeta && localStorage.getItem('d-secret') !== null) {
        btnCreateDevice.setAttribute('disabled', 'disabled');
        btnDeleteDevice.removeAttribute('disabled');
    } else {
        btnCreateDevice.setAttribute('disabled', 'disabled');
        btnDeleteDevice.setAttribute('disabled', 'disabled');
    }

    const urlDeviceConfirmEndpoint = "{{Route::has('cognito.action.user.device.create') ? (route('cognito.action.user.device.create')) : 'null'}}";
    const urlDeviceDeleteEndpoint = "{{Route::has('cognito.action.user.device.delete') ? (route('cognito.action.user.device.delete')) : 'null'}}";
    const CSRF_TOKEN_DEVICE = '{{ csrf_token() }}';

    async function confirmDevice() {
        try {
            //Build the device secret verifier payload
            let payload = buildDeviceSecretVerifier();

            let response = await fetch(urlDeviceConfirmEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN_DEVICE
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error('Failed to confirm device');
            }

            //Disable the create button and enable the delete button
            btnCreateDevice.setAttribute('disabled', 'disabled');
            btnDeleteDevice.removeAttribute('disabled');

            console.log(response);
            localStorage.setItem('d-confirm-response', JSON.stringify(response));

            this.successAlert('Device confirmed successfully.');
        } catch (error) {
            console.error('Error confirming device:', error);
            this.errorAlert(error.message || 'Device confirmation failed. Check the console for details.');
        }
    }

    async function deleteDevice() {
        try {
            let response = await fetch(urlDeviceDeleteEndpoint, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN_DEVICE
                },
                body: JSON.stringify({
                    device_key: localStorage.getItem('d-key')
                })
            });

            if (!response.ok) {
                throw new Error('Failed to delete device');
            }

            //Enable the create button and disable the delete button
            btnCreateDevice.removeAttribute('disabled');
            btnDeleteDevice.setAttribute('disabled', 'disabled');

            console.log(response);

            localStorage.removeItem('d-key');
            localStorage.removeItem('d-grp');
            localStorage.removeItem('d-secret');

            this.successAlert('Device deleted successfully.');
        } catch (error) {
            console.error('Error deleting device:', error);
            this.errorAlert(error.message || 'Device deletion failed. Check the console for details.');
        }
    }

    function buildDeviceSecretVerifier()
    {
        if (localStorage.getItem('d-secret') !== null) {
            throw new Error('Device secret verifier already exists. Delete the existing device key to create a new one.');
        }

        const N = BigInt("5809605995369958062791915965639201402176612226902900533702900882779736177890990861472094774477339581147373410185646378328043729800750470098210924487866935059164371588168047540943981644516632755067501626434556398193186628990071248660819361205119793693985433297036118232914410171876807536457391277857011849897410207519105333355801121109356897459426271845471397952675959440793493071628394122780510124618488232602464649876850458861245784240929258426287699705312584509625419513463605155428017165714465363094021609290561084025893662561222573202082865797821865270991145082200656978177192827024538990239969175546190770645685893438011714430426409338676314743571154537142031573004276428701433036381801705308659830751190352946025482059931306571004727362479688415574702596946457770284148435989129632853918392117997472632693078113129886487399347796982772784615865232621289656944284216824611318709764535152507354116344703769998514148343807");
        const g = BigInt("2");

        const randomBytes = CryptoJS.lib.WordArray.random(40);
        const devicePassword = CryptoJS.lib.WordArray.random(40).toString(CryptoJS.enc.Base64);
        console.log(devicePassword);
        localStorage.setItem('d-secret', devicePassword);

        const deviceGroupKey = localStorage.getItem('d-grp');
        const deviceKey = localStorage.getItem('d-key');
        let fullPassword = `${deviceGroupKey}${deviceKey}:${devicePassword}`;
        console.log(fullPassword);

        let fullPasswordHash = CryptoJS.SHA256(fullPassword).toString(CryptoJS.enc.Hex);
        console.log(fullPasswordHash);

        const randomSalt = CryptoJS.lib.WordArray.random(16);
        const saltHex = wordArrayToUnsignedHex(randomSalt);
        console.log('salt: ' + saltHex);
        localStorage.setItem('d-salt', saltHex);

        let xHash = hexHash(saltHex + fullPasswordHash);
        console.log('xHash: ' + xHash);

        const x = BigInt("0x" + xHash);
        const verifier = modPow(g, x, N);
        console.log(verifier);

        const verifierHex = bigintToUnsignedHex(verifier);
        console.log(verifierHex);

        //Save the salt to local storage to be used during device authentication
        localStorage.setItem('d-salt', CryptoJS.enc.Hex.parse(saltHex).toString(CryptoJS.enc.Base64));
        localStorage.setItem('d-xHash', xHash);

        return {
            'device_key': deviceKey,
            'device_name': navigator.appCodeName,
            'device_config': JSON.stringify({'DeviceSecretVerifierConfig': {
                'Salt': CryptoJS.enc.Hex.parse(saltHex).toString(CryptoJS.enc.Base64),
                'PasswordVerifier': CryptoJS.enc.Hex.parse(verifierHex).toString(CryptoJS.enc.Base64)
            }})
        };
    }

    function wordArrayToUnsignedHex(wordArray) {
        let hex = wordArray.toString(CryptoJS.enc.Hex);

        // if high bit set => prepend 00
        const firstByte = parseInt(hex.slice(0, 2), 16);

        if (firstByte & 0x80) {
            hex = "00" + hex;
        }

        return hex;
    }

    function hexHash(hex) {
        const wordArray = CryptoJS.enc.Hex.parse(hex);

        return CryptoJS.SHA256(wordArray)
            .toString(CryptoJS.enc.Hex);
    }

    function modPow(base, exponent, modulus) {
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
        }

        return result;
    }

    function bigintToUnsignedHex(bigint) {
        let hex = bigint.toString(16);

        // even length
        if (hex.length % 2) {
            hex = "0" + hex;
        }

        // if high bit set => prepend 00
        // prevents negative BigInteger interpretation
        const firstByte = parseInt(hex.slice(0, 2), 16);

        if (firstByte & 0x80) {
            hex = "00" + hex;
        }

        return hex;
    }
</script>
@endsection
