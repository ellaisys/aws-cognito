@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Resend Verification Code') }}
                </div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <x-cognito::forms.register-resendcode-form />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
