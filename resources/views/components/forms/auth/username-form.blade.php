<form method="POST" name="login-form">
    @csrf

    <div class="row mb-3">
        <label for="username" class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input id="username" type="email"
                class="form-control @error('username') is-invalid @enderror"
                name="username" value="{{ old('username') }}"
                required autocomplete="email" autofocus />

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

            @if (Route::has('cognito.form.register'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.register') }}">
                    {{ __('Register?') }}
                </a>
            @endif
        </div>
    </div>
</form>
