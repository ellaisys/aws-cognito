<ul class="navbar-nav ms-auto">
    <!-- Authentication Links -->
    @guest
        @if (Route::has(config('cognito.web_prefix', '').'.form.login'))
            <li class="nav-item">
                @if(Route::is(config('cognito.web_prefix', '').'.form.login'))
                    <button class="nav-link active disabled" aria-disabled="true">{{ __('Login') }}</button>
                @else
                <a class="nav-link"
                    href="{{ route(config('cognito.web_prefix', '').'.form.login') }}">{{ __('Login') }}</a>
                @endif
            </li>
        @endif

        @if (Route::has(config('cognito.web_prefix', '').'.form.register'))
            <li class="nav-item">
                @if(Route::is(config('cognito.web_prefix', '').'.form.register'))
                    <button class="nav-link active disabled" aria-disabled="true">{{ __('Register') }}</button>
                @else
                    <a class="nav-link"
                        href="{{ route(config('cognito.web_prefix', '').'.form.register') }}">{{ __('Register') }}</a>
                @endif
            </li>
        @endif
    @else
        <li class="nav-item dropdown">
            <button id="navbarDropdown" class="nav-link dropdown-toggle"
                data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false" v-pre
                onKeydown="event.preventDefault(); this.click();">
                {{ Auth::user()->name }}
            </button>
            <x-cognito::common.nav-menu />
        </li>
    @endguest
</ul>
