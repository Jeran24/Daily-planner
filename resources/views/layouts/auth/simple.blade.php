<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ session('theme', 'system') }}">
    <head>
        @include('partials.head')
    </head>
    <body class="planner-body">
        <div class="auth-layout">
            <aside class="auth-promo">
                <a class="planner-brand auth-brand" href="{{ route('home') }}" aria-label="{{ __('Daily Planner home') }}">DP</a>
                <div>
                    <h1>{{ __('Plan your day. Keep your streak.') }}</h1>
                    <p>{{ __('Tasks, goals, reminders, and a journal that stay yours.') }}</p>
                </div>
            </aside>
            <main class="auth-main">
                <a class="planner-brand auth-mobile-brand" href="{{ route('home') }}" aria-label="{{ __('Daily Planner home') }}">DP</a>
                <div class="auth-card">{{ $slot }}</div>
            </main>
        </div>
        @fluxScripts
    </body>
</html>
