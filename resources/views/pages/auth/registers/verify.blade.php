@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Verify Your Email Address') }}.</br>
                <small>{{ __('Before proceeding, please check your email for a verification link.') }}</small>
                </div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <x-cognito::forms.register-verify-form />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
