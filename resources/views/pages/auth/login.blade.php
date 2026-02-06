@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <x-cognito::forms.login-form />
                </div>
            </div>
        </div>
    </div>

    <x-cognito::mfa.code-form />
</div>
@endsection
