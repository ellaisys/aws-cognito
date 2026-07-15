@props([
    'challengeNameValue',
    'challengeValuePlaceholder',
])

<div class="d-inline-flex p-0">
    <label for="pass_code" class="col-md-4 col-form-label text-md-end"
        id="challenge_value_label">{{ __('cognito::messages.pass_code') }}</label>

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
