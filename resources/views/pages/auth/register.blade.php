<x-layouts::auth :title="__('Create owner account')">
    <div class="auth-flow">
        <header><h1>{{ __('Create the owner account') }}</h1><p class="planner-muted">{{ __('This is a private planner for one account.') }}</p></header>
        <x-auth-session-status class="text-center" :status="session('status')" />
        <form method="POST" action="{{ route('register.store') }}" class="auth-form">
            @csrf
            <div><label for="name">{{ __('Name') }}</label><input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name">@error('name')<p class="form-error" role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="email">{{ __('Email address') }}</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<p class="form-error" role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="password">{{ __('Password') }}</label><input id="password" name="password" type="password" required autocomplete="new-password" minlength="12"><p class="planner-muted form-help">{{ __('Use at least 12 characters.') }}</p>@error('password')<p class="form-error" role="alert">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation">{{ __('Confirm password') }}</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" minlength="12"></div>
            <button class="planner-button planner-button-primary" type="submit" data-test="register-user-button">{{ __('Create account') }}</button>
        </form>
        <p class="auth-footer">{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
    </div>
</x-layouts::auth>
