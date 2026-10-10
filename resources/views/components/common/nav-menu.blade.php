<div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
    @if (Route::has('cognito.form.change.password'))
    <a class="dropdown-item" href="{{ route('cognito.form.change.password') }}">
        {{ __('Change Password') }}
    </a>
    @endif

    @if (Route::has('cognito.form.user.invite'))
    <a class="dropdown-item" href="{{ route('cognito.form.user.invite') }}">
        {{ __('Invite User') }}
    </a>
    @endif

    @if (config('cognito.mfa_setup')!='OFF')
        <div class="dropdown-divider"></div>

        <button type="button" class="dropdown-item" disabled
            data-role="mfa" data-action="activate">
            {{ __('cognito::messages.mfa.activate') }}
        </button>

        <button type="button" class="dropdown-item" disabled
            data-role="mfa" data-action="deactivate">
            {{ __('cognito::messages.mfa.deactivate') }}
        </button>

        <div class="dropdown-divider"></div>

        <button type="button" class="dropdown-item" disabled
            data-role="mfa" data-action="enable"
            data-userkey="{{ base64_encode(Auth::user()->email) }}">
            {{ __('cognito::messages.mfa.enable') }}
        </button>

        <button type="button" class="dropdown-item" disabled
            data-role="mfa" data-action="disable"
            data-userkey="{{ base64_encode(Auth::user()->email) }}">
            {{ __('cognito::messages.mfa.disable') }}
        </button>
    @endif

    @if (config('cognito.allow_passkeys'))
        <div class="dropdown-divider"></div>

        @php
            $passkeyEnabled = (Auth::user() && isset(Auth::user()->is_webauthn_enabled)) ? Auth::user()->is_webauthn_enabled : false;
        @endphp

        @if (Route::has('cognito.action.user.passkey.delete') && $passkeyEnabled)
        <button type="button" class="dropdown-item"
            data-role="passkey-webauthn" data-action="delete"
            data-userkey="{{ base64_encode(Auth::user()->email) }}">
            {{ __('cognito::messages.passkey.delete') }}
        </button>
        @endif
    @endif

    @if (config('cognito.user_pool_device_enabled'))
        <div class="dropdown-divider"></div>

        @if (Route::has('cognito.action.user.device.create'))
        <button class="dropdown-item"
            data-role="device-auth" data-action="register">
            {{ __('cognito::messages.device.register') }}
        </button>
        @endif

        @if (Route::has('cognito.action.user.device.delete'))
        <button class="dropdown-item"
            data-role="device-auth" data-action="delete">
            {{ __('cognito::messages.device.unregister') }}
        </button>
        @endif
    @endif

    <div class="dropdown-divider"></div>

    @if (Route::has('cognito.logout'))
    <button class="dropdown-item"
        onclick="event.preventDefault();
        frmAction=document.getElementById('form-action');
        frmAction.action='{{ route('cognito.logout') }}';
        frmAction.submit();">
        {{ __('cognito::messages.auth.nav_label_logout') }}
    </button>
    @endif

    @if (Route::has('cognito.logout_forced'))
    <button class="dropdown-item"
        onclick="event.preventDefault();
        frmAction=document.getElementById('form-action');
        frmAction.action='{{ route('cognito.logout_forced') }}';
        frmAction.submit();">
        {{ __('cognito::messages.auth.nav_label_logout_forced') }}
    </button>
    @endif

    <form id="form-action" method="POST" class="d-none" action="#">
        @csrf
    </form>
</div>
