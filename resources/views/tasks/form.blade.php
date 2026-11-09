<x-layouts::app :title="$task ? __('Edit task') : __('Add custom task')">
    <section class="page-heading"><p class="planner-muted"><a href="{{ route('tasks.index') }}">{{ __('Tasks and routines') }}</a></p><h1>{{ $task ? __('Edit task routine') : __('Add custom task') }}</h1></section>
    <x-settings-navigation />
    <form class="planner-form-card" method="POST" action="{{ $task ? route('tasks.update', $task) : route('tasks.store') }}">
        @csrf @if ($task) @method('PUT') @endif
        <label class="planner-field"><span>{{ __('Task name') }}</span><input name="name" required maxlength="120" value="{{ old('name', $task?->name) }}" autocomplete="off">@error('name')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-row">
            <label class="planner-field"><span>{{ __('Category') }}</span><select name="category" required>@foreach (['faith'=>'Faith','health'=>'Health','coding'=>'Coding','study'=>'Study','other'=>'Other'] as $value => $label)<option value="{{ $value }}" @selected(old('category', $task?->category ?? 'other') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="planner-field"><span>{{ __('Start time') }}</span><input type="time" name="start_time" required value="{{ old('start_time', $task ? substr($task->start_time, 0, 5) : '09:00') }}"></label>
            <label class="planner-field"><span>{{ __('Duration in minutes') }}</span><input type="number" name="duration_minutes" required min="5" max="1440" step="5" value="{{ old('duration_minutes', $task?->duration_minutes ?? 60) }}"></label>
        </div>
        <label class="planner-field"><span>{{ __('Link to a goal (optional)') }}</span><select name="goal_id"><option value="">{{ __('No linked goal') }}</option>@foreach ($goals as $goal)<option value="{{ $goal->id }}" @selected((string) old('goal_id', $task?->goal_id) === (string) $goal->id)>{{ $goal->title }}</option>@endforeach</select>@error('goal_id')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-row">
            <label class="planner-field"><span>{{ __('Remind before (minutes)') }}</span><input type="number" name="remind_minutes_before" min="0" max="1440" required value="{{ old('remind_minutes_before', $task?->remind_minutes_before ?? $defaults['minutes']) }}">@error('remind_minutes_before')<small>{{ $message }}</small>@enderror</label>
            <label class="planner-toggle-row"><span><strong>{{ __('Loud alarm') }}</strong><small>{{ __('Plays while the planner is open. A real alarm needs the Android app.') }}</small></span><input type="checkbox" name="alarm_enabled" value="1" @checked(old('alarm_enabled', $task?->alarm_enabled ?? $defaults['alarm']))><span class="planner-toggle-track" aria-hidden="true"></span></label>
        </div>
        <fieldset class="planner-weekdays"><legend>{{ __('Repeat on') }}</legend><div class="planner-weekday-options">@foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $index => $day)<label><input type="checkbox" name="weekdays[]" value="{{ $index }}" @checked(in_array($index, old('weekdays', $task?->weekdays ?? [0,1,2,3,4,5,6])))><span>{{ $day }}</span></label>@endforeach</div>@error('weekdays')<small>{{ $message }}</small>@enderror @error('weekdays.*')<small>{{ $message }}</small>@enderror</fieldset>
        <label class="planner-field"><span>{{ __('Minimum version (optional)') }}</span><textarea name="minimum_version" rows="3" maxlength="500" placeholder="{{ __('What counts as doing the minimum?') }}">{{ old('minimum_version', $task?->minimum_version) }}</textarea><small>{{ __('If set, Today will offer a “Did the minimum” action.') }}</small>@error('minimum_version')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-actions"><button class="planner-button planner-button-primary" type="submit">{{ $task ? __('Save changes') : __('Add task') }}</button><a class="planner-button planner-button-outline" href="{{ route('tasks.index') }}">{{ __('Cancel') }}</a></div>
    </form>
</x-layouts::app>
