<form method="POST" action="{{ route('cognito.action.invite.submit') }}">
    @csrf

    @php
        $userFields = config('cognito.cognito_user_fields');

        $userNameField = $userFields['name'];
        $userEmailField = $userFields['email'];
        $userPhoneField = $userFields['phone_number'];
    @endphp

    @if(!empty($userNameField))
    <div class="row mb-3">
        <label for="{{ $userNameField }}" class="col-md-4 col-form-label text-md-end">{{ __('Name') }}</label>

        <div class="col-md-6">
            <input id="{{ $userNameField }}" type="text"
                class="form-control @error($userNameField) is-invalid @enderror"
                name="{{ $userNameField }}" value="{{ old($userNameField) }}"
                required autocomplete="name" autofocus />

            @error($userNameField)
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    @endif

    @if(!empty($userEmailField))
    <div class="row mb-3">
        <label for="{{ $userEmailField }}"
            class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

        <div class="col-md-6">
            <input id="{{ $userEmailField }}" type="email"
                class="form-control @error($userEmailField) is-invalid @enderror"
                name="{{ $userEmailField }}" value="{{ old($userEmailField) }}"
                required autocomplete="off" />

            @error($userEmailField)
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    @endif

    @if(!empty($userPhoneField))
    <div class="row mb-3">
        <label for="{{ $userPhoneField }}"
            class="col-md-4 col-form-label text-md-end">{{ __('Phone Number') }}</label>

        <div class="col-md-6">
            <input id="{{ $userPhoneField }}" type="tel"
                class="form-control @error($userPhoneField) is-invalid @enderror"
                name="{{ $userPhoneField }}" value="{{ old($userPhoneField) }}"
                minlength="10" maxlength="15"
                pattern="^\+\d{1,14}$"
                placeholder="+14325551212"
                required autocomplete="off" />

            @error($userPhoneField)
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
    @endif

    @if(Config::get('cognito.force_new_user_password'))
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
        <label for="password-confirm" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

        <div class="col-md-6">
            <input id="password-confirm" type="password"
                class="form-control" name="password_confirmation"
                required autocomplete="off" />
        </div>
    </div>
    @endif

    <div class="row mb-0">
        <div class="col-md-6 offset-md-4">
            <button type="submit" class="btn btn-primary">
                {{ __('Invite') }}
            </button>

            @if (Route::has('cognito.home'))
                <a class="btn btn-link float-end" href="{{ route('cognito.home') }}">
                    {{ __('Back?') }}
                </a>
            @endif
        </div>
    </div>
</form>
