<form id="auth-challenge-form" method="POST" action="{{ route('cognito.action.auth.challenge.submit') }}">

    <x-cognito-challenge
        :challenge-form-name="'auth-challenge-form'" />
        
    @php
        $data = (session('data')) ?? null;
        $challengeNameValue = 'NONE';

        if ($data && isset($data['status']) && $data['status'] == 'challenge') {
            $challengeNameValue = isset($data['challenge_name']) ?
                strtoupper($data['challenge_name']) :
                $challengeNameValue;
        } //End if
    @endphp

    <div class="row mb-3">
        @stack('cognito-challenge-passcode')
    </div>

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" class="btn btn-primary"
                data-action="challenge-submit" data-role="{{ $challengeNameValue }}">
                {{ __('Login') }}
            </button>

            @if (Route::has('cognito.form.register'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.register') }}">
                    {{ __('Register?') }}
                </a>
            @endif
        </div>
    </div>
</form>

@stack('cognito-challenge-scripts')
