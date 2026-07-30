<form method="POST" name="login-form">
    @csrf

    @php
        $usernameValue = (session('username'))? session('username') : null;
        $sessionValue = (session('session'))? session('session') : null;
    @endphp

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input type="hidden" id="session" name="session" value="{{ $sessionValue }}" />
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror @if($usernameValue) is-valid @endif"
                name="username" value="{{ old('username', $usernameValue) }}"
                @if($usernameValue) readonly autocomplete="off" @else required autocomplete="email" autofocus @endif />

            @error('username')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            @if (config('cognito.allow_passkeys'))
            <button type="submit" class="btn btn-primary" formaction="{{ route('cognito.form.login') }}/options">
                {{ __('Next >') }}
            </button>
            @endif

            <button type="submit" class="btn btn-outline-primary" formaction="{{ route('cognito.form.login') }}/password">
                {{ __('Login with password') }}
            </button>

            @if (Route::has('cognito.form.register') && config('cognito.registration_enabled', true))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.register') }}">
                    {{ __('Register?') }}
                </a>
            @endif
        </div>
    </div>
</form>
