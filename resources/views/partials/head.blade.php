<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Daily Planner') : config('app.name', 'Daily Planner') }}</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="planner-reminder-due-url" content="{{ route('reminders.due') }}">
<script nonce="{{ Illuminate\Support\Facades\Vite::cspNonce() }}">
    (() => {
        const applyTheme = (theme) => {
            const dark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.theme = theme;
            document.documentElement.classList.toggle('dark', dark);
        };
        applyTheme(document.documentElement.dataset.theme || 'system');
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('theme-updated', (event) => applyTheme(event.theme));
        });
    })();
</script>
@vite(['resources/css/app.css', 'resources/js/app.js'])
