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

    <div class="dropdown-divider"></div>

    @php
        $mfaEnabled = config('cognito.mfa_setup')=='MFA_NONE' ? false : true;
    @endphp

    @if (Route::has('cognito.form.user.mfa.activate') && $mfaEnabled)
    <a class="dropdown-item" href="{{ route('cognito.form.user.mfa.activate') }}">
        {{ __('Activate MFA') }}
    </a>
    @endif

    @if (Route::has('cognito.action.user.mfa.deactivate') && $mfaEnabled)
    <a class="dropdown-item" href="{{ route('cognito.action.user.mfa.deactivate') }}">
        {{ __('Deactivate MFA') }}
    </a>
    @endif

    <div class="dropdown-divider"></div>

    @if (Route::has('cognito.action.mfa.enable') && $mfaEnabled)
    <a class="dropdown-item" href="{{ route('cognito.action.mfa.enable') }}">
        {{ __('Enable MFA') }}
    </a>
    @endif

    @if (Route::has('cognito.action.mfa.disable') && $mfaEnabled)
    <a class="dropdown-item" href="{{ route('cognito.action.mfa.disable') }}">
        {{ __('Disable MFA') }}
    </a>
    @endif

    <div class="dropdown-divider"></div>

    @php
        $passkeyEnabled = (Auth::user() && isset(Auth::user()->is_webauthn_enabled)) ? Auth::user()->is_webauthn_enabled : false;
    @endphp

    @if (Route::has('cognito.action.user.passkey.delete') && config('cognito.allow_passkeys') && $passkeyEnabled)
    <button type="button" class="dropdown-item"
        data-role="passkey-webauthn" data-action="delete"
        data-userkey="{{ base64_encode(Auth::user()->email) }}">
        {{ __('Delete Passkey') }}
    </button>
    @endif

    <div class="dropdown-divider"></div>

    @if (Route::has('cognito.action.user.device.create'))
    <button id="create-device" class="dropdown-item"
        onclick="event.preventDefault(); confirmDevice();">
        {{ __('Create Device') }}
    </button>
    @endif

    @if (Route::has('cognito.action.user.device.delete'))
    <button id="delete-device" class="dropdown-item"
        onclick="event.preventDefault(); deleteDevice();">
        {{ __('Delete Device') }}
    </button>
    @endif

    <div class="dropdown-divider"></div>

    @if (Route::has('cognito.logout'))
    <button class="dropdown-item"
        onclick="event.preventDefault();
        frmAction=document.getElementById('form-action');
        frmAction.action='{{ route('cognito.logout') }}';
        frmAction.submit();">
        {{ __('Logout') }}
    </button>
    @endif

    @if (Route::has('cognito.logout_forced'))
    <button class="dropdown-item"
        onclick="event.preventDefault();
        frmAction=document.getElementById('form-action');
        frmAction.action='{{ route('cognito.logout_forced') }}';
        frmAction.submit();">
        {{ __('Logout (Forced)') }}
    </button>
    @endif

    <form id="form-action" method="POST" class="d-none" action="#">
        @csrf
    </form>
</div>
