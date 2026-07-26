@props([
    'challengeNameValue',
    'challengeParamsValue',
    'challengeValuePlaceholder',
])

@if(in_array($challengeNameValue, ['SELECT_MFA_TYPE']))
    <div class="d-inline-flex p-0">
        <label for="pass_code" class="col-md-4 col-form-label text-md-end"
            id="challenge_value_label">{{ __('cognito::messages.view_component.challenge.password.select_mfa_type') }}</label>

        @php
            $challengeParams = json_decode($challengeParamsValue, true);
            $mfaOptions = json_decode($challengeParams['MFAS_CAN_CHOOSE'] ?? '[]', true);
        @endphp

        <div class="col-md-6">
            <select id="pass_code" name="pass_code"
                class="form-control @error('pass_code') is-invalid @enderror"
                required autocomplete="off">

                <option value="" disabled selected>{{ __('cognito::messages.view_component.challenge.password.select_mfa_type') }}</option>
                @foreach($mfaOptions as $mfaOption)
                    <option value="{{ $mfaOption }}">
                        {{ $mfaOption }}
                    </option>
                @endforeach
            </select>
            @error('pass_code')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
@else
    <div class="d-inline-flex p-0">
        <label for="pass_code" class="col-md-4 col-form-label text-md-end"
            id="challenge_value_label">{{ __('cognito::messages.view_component.challenge.password.pass_code') }}</label>

        <div class="col-md-6">
            <input type="{{ in_array($challengeNameValue, ['PASSWORD', 'PASSWORD_VERIFIER']) ? 'password' : 'text' }}"
                id="pass_code" name="pass_code"
                class="form-control @error('pass_code') is-invalid @enderror"
                placeholder="{{ $challengeValuePlaceholder ?? '' }}"
                required autocomplete="off" />

            @error('pass_code')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
@endif
