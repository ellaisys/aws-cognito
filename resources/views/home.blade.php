@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-2">
                <div class="card-header">{{ __('Dashboard') }}</div>

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

<button id="enable-passkeys-button" class="btn btn-primary">Enable Passkeys</button>

<script>
    const urlPasskeyStartEndpoint = "{{Route::has('cognito.action.user.passkey.start') ? (route('cognito.action.user.passkey.start')) : 'null'}}";
    const urlPasskeyCompleteEndpoint = "{{Route::has('cognito.action.user.passkey.complete') ? (route('cognito.action.user.passkey.complete')) : 'null'}}";
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
            console.log('Passkey registration completed:', completePayload);
            alert('Passkey registered successfully.');
        } catch (error) {
            console.error('Error enabling passkey:', error);
            alert('Passkey registration failed. Check the console for details.');
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
</script>
@endsection
