@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <form method="POST" action="{{ route('cognito.action.password.reset') }}">
                        @csrf

                        @if(request()->has('code'))
                            <input type="hidden" name="code" value="{{ request()->get('code') }}" />
                        @elseif(request()->has('token'))
                            <input type="hidden" name="token" value="{{ request()->get('token') }}" />
                        @else
                            <div class="row mb-3">
                                <label for="token" class="col-md-4 col-form-label text-md-end">{{ __('Token') }}</label>

                                <div class="col-md-6">
                                    <input id="token" type="text"
                                        class="form-control @error('token') is-invalid @enderror"
                                        name="token"
                                        value="{{ request()->has('token') ? request()->get('token') : old('token') }}"
                                        autocomplete="off"
                                        {{ request()->has('token') ? 'disabled' : 'required autofocus' }}/>

                                    @error('token')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="row mb-3">
                            <label for="email"
                                class="col-md-4 col-form-label text-md-end">{{ __('Email Address') }}</label>

                            <div class="col-md-6">
                                @if(request()->has('email'))
                                <input type="hidden" name="email" value="{{ request()->get('email') }}" />
                                @endif
                                <input id="email" type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    name="email"
                                    value="{{ request()->has('email') ? request()->get('email') : old('email') }}"
                                    autocomplete="off"
                                    {{ request()->has('email') ? 'disabled' : 'required autofocus' }} />

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password"
                                class="col-md-4 col-form-label text-md-end">{{ __('Password') }}</label>

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
                            <label for="password-confirm"
                                class="col-md-4 col-form-label text-md-end">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password"
                                    class="form-control"
                                    name="password_confirmation"
                                    required autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Reset Password') }}
                                </button>

                                @if (Route::has('cognito.form.login'))
                                    <a class="btn btn-link float-end" href="{{ route('cognito.form.login') }}">
                                        {{ __('Cancel? Go To Login') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
