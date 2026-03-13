@if ((config('cognito.mfa')!='MFA_NONE') && (request()->has('status')) && (request()->has('session_token')))
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Token') }}: {{ request('status') }}</div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <form method="POST" action="{{ route('cognito.action.mfa.code.submit') }}">
                        @csrf

                        <input type="hidden" id="challenge_name" name="challenge_name" value="{{ request('status') }}">
                        <input type="hidden" id="session" name="session" value="{{ request('session_token') }}">

                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">{{ __('Code') }}</label>

                            <div class="col-md-6">
                                <input type="text" id="mfa_code" name="mfa_code"
                                    class="form-control @error('mfa_code') is-invalid @enderror"
                                    value="" minlength="4"
                                    autocomplete="email" required autofocus />

                                @error('mfa_code')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
