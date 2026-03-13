@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Change Password') }}</div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <form method="POST" action="{{ route('cognito.action.change.password') }}">
                        @csrf

                        @if(request()->has('challenge_name'))
                            <input type="hidden" name="challenge_name" value="{{ request()->get('challenge_name') }}" />
                            <input type="hidden" name="session_token" value="{{ request()->get('session_token') }}" />
                            <input id="email" type="hidden" name="email" value="{{ request()->get('email') }}" />
                        @endif

                        <div class="row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-md-end">{{ __('Existing Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    name="password" value=""
                                    autocomplete="off" required autofocus />

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="new_password" class="col-md-4 col-form-label text-md-end">{{ __('New Password') }}</label>

                            <div class="col-md-6">
                                <input id="new_password" type="password"
                                    class="form-control @error('new_password') is-invalid @enderror"
                                    name="new_password" required autocomplete="off" />

                                @error('new_password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="new_password_confirmation" class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="new_password_confirmation" type="password"
                                    class="form-control"
                                    name="new_password_confirmation"
                                    required autocomplete="off" />
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save') }}
                                </button>

                                <a class="btn btn-link float-end" href="{{ url()->previous() }}">
                                    {{ __('Back') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
