@if (Route::has('cognito.action.user.mfa.activate'))
    @if ($actionActivateMFA = session()->get('actionActivateMFA'))
        <div class="card">
            <div class="card-header">{{ __('MFA Code') }}</div>

            <div class="card-body">
                <form name="verify-mfa-code-form" id="verify-mfa-code-form"
                    method="post" action="{{route('cognito.action.user.mfa.activate')}}" autocomplete="off">
                    @csrf

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Activation QR Code</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="flex-fill font-regular text-nowrap">
                                <small>Key: {{ $actionActivateMFA['SecretCode'] }}</small>
                                <input type="hidden" name="mfa_secret_code" id="mfa_secret_code"
                                    value="{ $actionActivateMFA['SecretCode'] }}" />
                            </div>
                            <div class="flex-fill">
                                <img src="{{ $actionActivateMFA['SecretCodeQR'] }}" alt="QR Code"
                                    class="mx-auto d-block img-thumbnail" />
                            </div>
                            <div class="flex-fill">
                                <a href="{{ $actionActivateMFA['TotpUri'] }}" target="_blank">TOTP Link</a>
                            </div>

                            <div class="flex-fill form-group mt-4 w-50">
                                <input type="text" name="code" id="code"
                                    class="form-control" placeholder="Enter the code"
                                    pattern="[0-9]{6}" autocomplete="off" maxlength="6"
                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                                    tabindex="-2" autofocus required />
                            </div>
                            <div class="flex-fill form-group mt-1 w-50">
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
        </div>
    @endif
@endif
