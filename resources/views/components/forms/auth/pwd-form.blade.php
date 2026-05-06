<form method="POST" action="{{ route('cognito.action.login.submit') }}" id="auth-password-form">
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
                @if($usernameValue) readonly @else required autocomplete="email" autofocus @endif
                />

            @error('username')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

        <div class="col-md-6">
            <input id="password" type="password"
                class="form-control @error('password') is-invalid @enderror"
                name="password" required autocomplete="off" />

            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6 offset-md-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                    name="remember" id="remember"
                    value="{{ old('remember') ? 'checked' : '' }}" />

                <label class="form-check-label" for="remember">
                    {{ __('Remember Me') }}
                </label>
            </div>
        </div>
    </div>

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Login') }}
            </button>

            @if (Route::has('cognito.form.register'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.register') }}">
                    {{ __('Register?') }}
                </a>
            @endif
        </div>
    </div>

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            @if (Route::has('cognito.form.password.forgot'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.password.forgot') }}">
                    {{ __('Forgot Your Password?') }}
                </a>
            @endif
        </div>
    </div>

    @if (config('cognito.allow_passkeys'))
    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="button" id="auth-passkeys-button" class="btn btn-outline-dark btn-block"
                onclick="redirectToPasskeyOptions('options');">
                {{ __('Other ways to login ?') }}
            </button>
        </div>
    </div>
    @endif
</form>

<script>
    const urlAuthStep = "{{ route('cognito.form.login') }}";

    function redirectToPasskeyOptions(urlEndpoint) {
        if (urlEndpoint != null || urlEndpoint != undefined) {
            var password = document.getElementById('password')
            password.disabled = true;

            var remember = document.getElementById('remember')
            remember.disabled = true;
        }

        const form = document.getElementById('auth-password-form');
        form.method = 'POST';
        form.action = urlAuthStep + '/' + urlEndpoint;
        form.submit();
    }
</script>
