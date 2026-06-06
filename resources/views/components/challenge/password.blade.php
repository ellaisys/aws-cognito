<div class="row">
    <label for="pass_code" class="col-md-4 col-form-label text-md-end"
        id="challenge_value_label">{{ __('Pass Code') }}</label>

    <div class="col-md-6">
        <input id="pass_code" type="password"
            class="form-control @error('pass_code') is-invalid @enderror"
            name="pass_code" required autocomplete="off" />

        @error('pass_code')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>
</div>
