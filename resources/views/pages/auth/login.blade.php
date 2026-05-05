@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    @if (!((config('cognito.mfa')!='MFA_NONE') && (request()->has('status')) && (request()->has('session_token'))))
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}{{request()->route('step')}}</div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    @switch(request()->route('step'))
                        @case('old')
                            <x-cognito::forms.login-form />
                            @break
                        @case('options')
                            <x-cognito::forms.auth.options-form />
                            @break
                        @case('challenge')
                            <x-cognito::forms.auth.challenge-form />
                            @break
                        @case('password')
                            <x-cognito::forms.auth.pwd-form />
                            @break
                        @case('username')
                        @default
                            <x-cognito::forms.auth.username-form />
                            @break
                    @endswitch
                </div>
            </div>
        </div>
    </div>
    @endif
    <x-cognito::mfa.code-form />
</div>
@endsection
