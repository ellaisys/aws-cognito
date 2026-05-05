<form method="POST" id="auth-passkey-challenges-form">
    @csrf

    @php
        $usernameValue = (request()->has('username'))? request()->get('username') : null;
        $sessionValue = (request()->has('session'))? request()->get('session') : null;
    @endphp

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input type="hidden" id="session" name="session" value="{{ $sessionValue }}" />
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror @if($usernameValue) is-valid @endif"
                name="username" value="{{ old('username', $usernameValue) }}"
                @if($usernameValue) readonly @else required autocomplete="email" autofocus @endif />

            @error('username')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    @if (config('cognito.allow_passkeys'))
        <div class="col-md-6 offset-md-4 list-group" id="available-challenges-list">
        </div>
    @endif
</form>

<script>
    const urlPasskeyAuthChallenge = "{{Route::has('cognito.action.auth.passkey.challenge') ? (route('cognito.action.auth.passkey.challenge')) : 'null'}}";
    const AUTH_CSRF_TOKEN = '{{ csrf_token() }}';

    window.addEventListener('load', (event) => {
        getAvailableChallenges();
    });

    // Function to handle the passkey authentication process
    async function getAvailableChallenges() {
        try {
            const challengesList = document.getElementById('available-challenges-list');
            const sessionInput = document.getElementById('session');

            var response = await fetch(urlPasskeyAuthChallenge, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': AUTH_CSRF_TOKEN
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    username: "{{ $usernameValue }}"
                })
            });

            if (!response.ok) {
                throw new Error('Failed to get passkey authentication challenge');
            }

            responseData = await response.json();
            responseData = responseData.data || {};
            sessionValue = responseData.Session || null;
            sessionInput.value = sessionValue; // Update the session input field with the new value

            // Collect available challenges and update the UI
            availableChallenges = responseData.AvailableChallenges || [];

            // Fill the challenges list in the UI
            challengesList.innerHTML = ''; // Clear existing list
            if (availableChallenges.length > 0) {
                availableChallenges.forEach(challenge => {
                    if (challenge === 'PASSWORD_SRP') {
                        return; // Skip PASSWORD_SRP as it's handled separately
                    }

                    const item = document.createElement('button');
                    item.type = 'submit';
                    item.className = 'list-group-item list-group-item-action' + ((challenge === 'PASSWORD') ? ' active' : ''); // Highlight PASSWORD option
                    item.textContent = toDisplayName(challenge); // Display the challenge name or type

                    if (challenge === 'PASSWORD') {
                        item.formAction = "{{ route('cognito.form.login') }}/password";
                    } else {
                        item.formAction = "{{ route('cognito.form.login') }}/challenge?" + new URLSearchParams({ challenge: challenge }).toString();
                    }
                    challengesList.appendChild(item);
                });
            } else {
                const noChallengesItem = document.createElement('button');
                noChallengesItem.type = 'submit';
                noChallengesItem.className = 'list-group-item list-group-item-action active';
                noChallengesItem.textContent = 'Password';
                challengesList.appendChild(noChallengesItem);
            }
        } catch (error) {
            console.error('Error authenticating passkey:', error);
        }
    }

    const toDisplayName = (str) => {
        return str
            .toLowerCase()
            .replace(/[-_ ]+/g, ' ')
            .replace(/^./, (char) => char.toUpperCase());
    };
</script>