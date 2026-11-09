<x-layouts::auth :title="__('Log in')">
    <div class="auth-flow">
        <header>
            <h1>{{ __('Welcome back') }}</h1>
            <p class="planner-muted">{{ __('Sign in to see your daily plan.') }}</p>
        </header>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf
            <div>
                <label for="email">{{ __('Email address') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="name@example.com">
                @error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror
            </div>
            <div>
                <div class="auth-label-row">
                    <label for="password">{{ __('Password') }}</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                    @endif
                </div>
                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="{{ __('Password') }}">
            </div>
            <label class="remember-row"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>{{ __('Remember me') }}</span></label>
            <button class="planner-button planner-button-primary" type="submit" data-test="login-button">{{ __('Sign in') }}</button>
        </form>

        @if (! \App\Models\User::query()->exists())
            <p class="auth-footer">{{ __('First time here?') }} <a href="{{ route('register') }}">{{ __('Create the owner account') }}</a></p>
        @endif
    </div>
</x-layouts::auth>
