<x-layouts::app :title="__('Reminders and alarms')">
    <section class="page-heading"><p class="planner-muted">{{ __('Settings') }}</p><h1>{{ __('Reminders and alarms') }}</h1></section>
    <x-settings-navigation />
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    <form class="planner-form-card settings-form" method="POST" action="{{ route('settings.reminders.update') }}">
        @csrf
        <p class="planner-page-intro">{{ __('These defaults apply when you add a new routine. Change a routine’s reminder from its edit form.') }}</p>
        <label class="planner-field"><span>{{ __('Remind me this many minutes before') }}</span><input type="number" name="remind_minutes_before" min="0" max="1440" required value="{{ old('remind_minutes_before', $minutes) }}">@error('remind_minutes_before')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-toggle-row"><span><strong>{{ __('Loud alarm') }}</strong><small>{{ __('Plays while the planner is open. A real alarm needs the Android app.') }}</small></span><input type="checkbox" name="alarm_enabled" value="1" @checked(old('alarm_enabled', $alarmEnabled))><span class="planner-toggle-track" aria-hidden="true"></span></label>
        <div class="reminder-controls" data-reminder-controls data-vapid-key="{{ $vapidPublicKey }}" data-subscribe-url="{{ route('reminders.subscribe') }}" data-unsubscribe-url="{{ route('reminders.unsubscribe') }}" data-test-url="{{ route('reminders.test') }}">
            <h2>{{ __('Browser reminders') }}</h2>
            <p class="planner-muted">{{ __('When this planner is open, it checks every minute and shows a banner with a chime. Browser notification permission is requested only when you choose to turn reminders on.') }}</p>
            @if ($pushEnabled)<p class="planner-flash" role="status">{{ __('Push reminders are enabled for at least one browser on this account.') }}</p>@endif
            <p class="planner-muted" data-notification-help>{{ __('If permission was denied, use your browser’s site settings to allow notifications. The in-page banner still works while the planner is open.') }}</p>
            <div class="planner-form-actions"><button class="planner-button planner-button-primary" type="button" data-enable-reminders>{{ __('Turn on reminders') }}</button><button class="planner-button planner-button-outline" type="button" data-test-reminder>{{ __('Send test reminder') }}</button>@if ($pushEnabled)<button class="planner-button planner-button-outline" type="button" data-disable-reminders>{{ __('Turn off push on this device') }}</button>@endif</div>
            <p class="planner-muted" data-reminder-status aria-live="polite"></p>
        </div>
        <p class="planner-muted">{{ __('A website cannot ring a true alarm while closed. Loud alarm: Plays while the planner is open. A real alarm needs the Android app.') }}</p>
        <button class="planner-button planner-button-primary" type="submit">{{ __('Save defaults') }}</button>
    </form>
</x-layouts::app>
