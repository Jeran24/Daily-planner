<x-layouts::app :title="__('Privacy and security settings')">
    <section class="page-heading"><p class="planner-muted">{{ __('Settings') }}</p><h1>{{ __('Privacy and security') }}</h1></section>
    <x-settings-navigation />
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    <form class="planner-form-card settings-form" method="POST" action="{{ route('settings.privacy.update') }}">
        @csrf
        <h2>{{ __('Idle lock') }}</h2>
        <p class="planner-muted">{{ __('After you are inactive, the planner asks for your password before showing your data again.') }}</p>
        <label class="planner-field"><span>{{ __('Lock after (minutes)') }}</span><input type="number" name="lock_timeout_minutes" min="1" max="120" required value="{{ old('lock_timeout_minutes', $timeout) }}">@error('lock_timeout_minutes')<small>{{ $message }}</small>@enderror</label>
        <button class="planner-button planner-button-primary" type="submit">{{ __('Save lock time') }}</button>
    </form>
    <section class="settings-device-section"><h2>{{ __('Logged-in devices') }}</h2><p class="planner-muted">{{ __('End a session you no longer recognize. Your current session cannot be ended here.') }}</p>
        @forelse ($devices as $device)
            <article class="planner-card settings-device"><div><strong>{{ $device['current'] ? __('This device') : __('Other device') }}</strong><p>{{ $device['user_agent'] }}</p><small>{{ $device['ip_address'] ?: __('IP unavailable') }} · {{ $device['last_activity'] }}</small></div>@unless ($device['current'])<form method="POST" action="{{ route('settings.devices.destroy', $device['id']) }}">@csrf @method('DELETE')<button class="planner-button planner-button-outline" type="submit">{{ __('Log out') }}</button></form>@endunless</article>
        @empty
            <article class="planner-card"><p>{{ __('No database sessions are available to list.') }}</p></article>
        @endforelse
    </section>
</x-layouts::app>
