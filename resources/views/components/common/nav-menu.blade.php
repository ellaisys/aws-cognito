<div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
    @if (Route::has('cognito.form.change.password'))
    <a class="dropdown-item" href="{{ route('cognito.form.change.password') }}">
        {{ __('Change Password') }}
    </a>
    @endif

    <div class="dropdown-divider"></div>

    @if (Route::has('cognito.form.user.mfa.activate'))
    <a class="dropdown-item" href="{{ route('cognito.form.user.mfa.activate') }}"
        data-toggle="modal" data-target="#modalMFAActivate">
        {{ __('Activate MFA') }}
    </a>
    @endif

    @if (Route::has('cognito.action.mfa.enable'))
    <a class="dropdown-item" href="{{ route('cognito.action.mfa.enable') }}">
        {{ __('Enable MFA') }}
    </a>
    @endif

    @if (Route::has('cognito.action.mfa.disable'))
    <a class="dropdown-item" href="{{ route('cognito.action.mfa.disable') }}">
        {{ __('Disable MFA') }}
    </a>
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
