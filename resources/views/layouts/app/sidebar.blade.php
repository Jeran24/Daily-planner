<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme ?? 'system' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="planner-body">
        <div class="planner-reminder-banner" data-planner-reminder-banner role="status" aria-live="assertive" hidden>
            <span data-planner-reminder-message></span>
            <button type="button" data-dismiss-reminder aria-label="{{ __('Dismiss reminder') }}">×</button>
        </div>
        <div class="planner-app">
            <aside class="planner-rail" aria-label="{{ __('Main navigation') }}">
                <a class="planner-brand" href="{{ route('home') }}" aria-label="{{ __('Daily Planner home') }}">DP</a>
                <x-navigation />
                <div class="planner-rail-bottom">
                    <span class="planner-avatar" aria-label="{{ auth()->user()->name }}">{{ auth()->user()->initials() }}</span>
                </div>
            </aside>
            <main class="planner-main">
                <header class="planner-topbar">
                    <div><span class="planner-muted">{{ __('Daily Planner') }}</span><strong>{{ auth()->user()->name }}</strong></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="planner-button planner-button-outline" type="submit" aria-label="{{ __('Log out') }}">{{ __('Log out') }}</button>
                    </form>
                </header>
                <div class="planner-content">{{ $slot }}</div>
            </main>
        </div>
        <x-navigation :mobile="true" />
        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist
        @fluxScripts
    </body>
</html>
