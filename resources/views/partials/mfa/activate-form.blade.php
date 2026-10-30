<div class="card-body modal-dialog">
    <form name="verify-mfa-code-form" id="verify-mfa-code-form"
        method="post" action="{{route('cognito.action.user.mfa.activate')}}" autocomplete="off">
        @csrf

        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalMFAActivateLabel" class="modal-title">{{ __('MFA Code') }}- Activation QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="flex-fill font-regular text-nowrap">
                    <small>Key: {{ $data['SecretCode'] }}</small>
                    <input type="hidden" name="mfa_secret_code" id="mfa_secret_code"
                        value="{{ $data['SecretCode'] ?? '' }}" />
                </div>

                <div class="flex-fill">
                    <img src="{{ $data['SecretCodeQR'] ?? '' }}" alt="QR Code"
                        class="mx-auto d-block img-thumbnail" />
                </div>

                <div class="flex-fill">
                    <a href="{{ $data['TotpUri'] ?? '' }}" target="_blank">TOTP Link</a>
                </div>

                <div class="flex-fill form-group mt-4 w-50">
                    <label for="code">{{ __('MFA Code') }}</label>
                    <input type="text" name="code" id="code"
                        class="form-control" placeholder="Enter the code"
                        pattern="[0-9]{6}" autocomplete="off" maxlength="6"
                        oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                        tabindex="-2" required />
                </div>

                <div class="flex-fill form-group mt-1 w-50">
                    <label for="device_name">{{ __('Device Name') }}</label>
                    <input type="text" name="device_name" id="device_name"
                        class="form-control" placeholder="Enter the device name"
                        value="My Phone" autocomplete="off"
                        tabindex="-1" required />
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                    data-bs-dismiss="modal" aria-label="Close">Close</button>
                <button type="submit" class="btn btn-primary">Verify</button>
            </div>
        </div>
    </form>
</div>
