<form method="POST" action="{{ route('cognito.action.register.verify') }}" name="verifyForm">
    @csrf

    @php
        $userFields = config('cognito.cognito_user_fields');
        $userEmailField = $userFields['email'];

        $userEmailField = $userFields['email'];
        $emailValue = (request()->has('email'))? request()->get('email') : null;
        $emailValue = urlencode($emailValue);
        if (str_contains($emailValue, '%40')) {
            $emailValue = str_replace('%40', '@', $emailValue);
        }

        $codeValue = (request()->has('code'))? request()->get('code') : null;
    @endphp

    <div class="row mb-3">
        <label for="code" class="col-md-4 col-form-label text-md-end">{{ __('Verification Code') }}</label>

        <div class="col-md-6">
            <input id="code" type="text"
                class="form-control @error('code') is-invalid @enderror"
                name="code" @if(!empty($codeValue)) value="{{ $codeValue }}" @endif
                required autocomplete="off" autofocus />

            @error('code')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

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
            <button type="submit" class="btn btn-primary">
                {{ __('Verify') }}
            </button>

            @if (Route::has('cognito.form.login'))
                <a class="btn btn-link float-end" href="{{ route('cognito.form.login') }}">
                    {{ __('Login?') }}
                </a>
            @endif

            @if (Route::has('cognito.form.register.resend_code'))
            <a href="{{ route('cognito.form.register.resend_code') }}" class="btn btn-link float-end">
                {{ __('If you did not receive the email') }},
                {{ __('Resend Code?') }}
            </a>
            @endif
        </div>
    </div>
</form>

@if(request()->has('code') && request()->has('email') &&
    request()->has('action') && request()->get('action') == 'verify')
<script>
    //document.verifyForm.submit();
</script>
@endif
