@extends(config('cognito.views.layout'))

@section('content')
<div class="container">
    <div class="accordion accordion-flush" id="accordionFlushExample">
        @if(!empty(session()->all()))
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                    Session Data (Debug)
                </button>
            </h2>
            <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">{{ print_r(session()->all(), true) }}</div>
            </div>
        </div>
        @endif

        @if(!empty(request()->all()))
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false" aria-controls="flush-collapseTwo">
                    Request Data (Debug)
                </button>
            </h2>
            <div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">{{ print_r(request()->all(), true) }}</div>
            </div>
        </div>
        @endif
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <x-cognito::common.alert />

                    @php
                        $step = request()->route('step');
                        $step = (bool) config('cognito.allow_passkeys', false) ? $step : 'password';
                    @endphp

                    @switch($step)
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
</div>
@endsection
