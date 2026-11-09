<x-layouts::auth.simple :title="__('Planner locked')">
    <div class="auth-flow">
        <header><h1>{{ __('Planner locked') }}</h1><p class="planner-muted">{{ __('Enter your password to continue.') }}</p></header>
        <form method="POST" action="{{ route('lock.unlock') }}" class="auth-form">
            @csrf
            <div><label for="password">{{ __('Password') }}</label><input id="password" type="password" name="password" required autofocus autocomplete="current-password">@error('password')<p class="form-error">{{ $message }}</p>@enderror</div>
            <button class="planner-button planner-button-primary" type="submit">{{ __('Unlock planner') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="planner-button planner-button-outline" type="submit">{{ __('Log out') }}</button></form>
    </div>
</x-layouts::auth.simple>
