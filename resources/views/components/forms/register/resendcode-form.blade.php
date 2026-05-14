<form method="POST" action="{{ route('cognito.action.register.resend_code') }}">
    @csrf

    @php
        $userFields = config('cognito.cognito_user_fields');

        $userEmailField = $userFields['email'];
        $emailValue = (request()->has('email'))? request()->get('email') : null;
        $emailValue = urlencode($emailValue);
        if (str_contains($emailValue, '%40')) {
            $emailValue = str_replace('%40', '@', $emailValue);
        }
    @endphp

    @if(!empty($userEmailField))
    <div class="row mb-3">
        <label for="{{ $userEmailField }}"
            class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input id="{{ $userEmailField }}" type="email"
                class="form-control @error($userEmailField) is-invalid @enderror"
                name="{{ $userEmailField }}" value="{{ old($userEmailField, $emailValue) }}"
                required autocomplete="off" />

            @error($userEmailField)
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    @endif

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit"
                class="btn btn-primary">
                {{ __('Click here to request code') }}</button>

            @if (Route::has('cognito.form.register.verify'))
            <a href="{{ route('cognito.form.register.verify') }}" class="btn btn-link float-end">
                {{ __('Verify?') }}
            </a>
            @endif

            @if (Route::has('cognito.form.login'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.login') }}">
                    {{ __('Login?') }}
                </a>
            @endif
        </div>
    </div>
</form>
