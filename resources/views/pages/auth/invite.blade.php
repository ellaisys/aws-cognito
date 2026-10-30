@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Invite User') }}</div>

                <div class="card-body">
                    <x-cognito::common.alert />
                    <x-cognito::forms.invite-form />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
