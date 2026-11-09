<x-layouts::app :title="__('Goals and calendar settings')">
    <section class="page-heading"><p class="planner-muted">{{ __('Settings') }}</p><h1>{{ __('Goals and calendar') }}</h1></section>
    <x-settings-navigation />
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    <form class="planner-form-card settings-form" method="POST" action="{{ route('settings.goals.update') }}">
        @csrf
        <label class="planner-field"><span>{{ __('Week starts on') }}</span><select name="week_starts_on" required>@foreach (['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day => $label)<option value="{{ $day }}" @selected((int) old('week_starts_on', $weekStartsOn) === $day)>{{ __($label) }}</option>@endforeach</select>@error('week_starts_on')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-field"><span>{{ __('Streak threshold (%)') }}</span><input type="number" name="streak_threshold" min="1" max="100" step="1" required value="{{ old('streak_threshold', $streakThreshold) }}"><small>{{ __('A day counts toward your streak when its completion reaches this percentage. Default: 80%.') }}</small>@error('streak_threshold')<small>{{ $message }}</small>@enderror</label>
        <button class="planner-button planner-button-primary" type="submit">{{ __('Save preferences') }}</button>
    </form>
</x-layouts::app>
