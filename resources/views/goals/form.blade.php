<x-layouts::app :title="$goal ? __('Edit goal') : __('Create goal')">
    <section class="page-heading"><p class="planner-muted"><a href="{{ route('goals') }}">{{ __('Goals') }}</a></p><h1>{{ $goal ? __('Edit goal') : __('Create a goal') }}</h1></section>
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    <form class="planner-form-card goal-form" method="POST" action="{{ $goal ? route('goals.update', $goal) : route('goals.store') }}">
        @csrf @if ($goal) @method('PUT') @endif
        <label class="planner-field"><span>{{ __('Goal title') }}</span><input name="title" required maxlength="160" value="{{ old('title', $goal?->title) }}">@error('title')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-row goal-date-row">
            <label class="planner-field"><span>{{ __('Start date') }}</span><input type="date" name="start_date" required value="{{ old('start_date', $goal?->start_date?->toDateString() ?? now(auth()->user()->timezone)->toDateString()) }}">@error('start_date')<small>{{ $message }}</small>@enderror</label>
            <label class="planner-field" id="end-date"><span>{{ __('Deadline') }}</span><input type="date" name="end_date" required value="{{ old('end_date', $goal?->end_date?->toDateString() ?? now(auth()->user()->timezone)->addDays(30)->toDateString()) }}">@error('end_date')<small>{{ $message }}</small>@enderror</label>
        </div>
        <label class="planner-field"><span>{{ __('How will you measure progress?') }}</span><select name="measure_type" required>@foreach (['checklist'=>'Checklist','minutes'=>'Minutes on linked tasks','task_count'=>'Completed linked tasks','manual'=>'Percentage I enter'] as $value => $label)<option value="{{ $value }}" @selected(old('measure_type', $goal?->measure_type ?? 'checklist') === $value)>{{ $label }}</option>@endforeach</select>@error('measure_type')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-field" id="target-value"><span>{{ __('Target value (minutes or tasks)') }}</span><input type="number" name="target_value" min="1" max="1000000" value="{{ old('target_value', $goal?->target_value) }}"><small>{{ __('Required for Minutes and Task count measures.') }}</small>@error('target_value')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-field"><span>{{ __('Current manual progress (%)') }}</span><input type="number" name="manual_percent" min="0" max="100" step="0.1" value="{{ old('manual_percent', $goal?->manual_percent) }}"><small>{{ __('Required for the Manual measure.') }}</small>@error('manual_percent')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-field"><span>{{ __('Checklist items, one per line') }}</span><textarea name="checklist_items" rows="5" maxlength="10000">{{ old('checklist_items', $goal?->measure_type === 'checklist' ? $goal->items->pluck('title')->join("\n") : '') }}</textarea><small>{{ __('Required for Checklist goals. You can check items after creating the goal.') }}</small>@error('checklist_items')<small>{{ $message }}</small>@enderror</label>
        @if ($goal)<label class="planner-field"><span>{{ __('Goal status') }}</span><select name="status">@foreach (['active'=>'Active','paused'=>'Paused','completed'=>'Completed'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $goal->status) === $value)>{{ $label }}</option>@endforeach</select></label>@endif
        <div class="planner-form-actions"><button class="planner-button planner-button-primary" type="submit">{{ $goal ? __('Save goal') : __('Create goal') }}</button><a class="planner-button planner-button-outline" href="{{ route('goals') }}">{{ __('Cancel') }}</a></div>
    </form>

    @if ($goal && $goal->measure_type === 'checklist')
        <section class="goal-checklist-section"><h2>{{ __('Checklist') }}</h2>
            @foreach ($goal->items as $item)
                <article class="planner-task-item goal-checklist-item"><form method="POST" action="{{ route('goals.items.toggle', [$goal, $item]) }}">@csrf @method('PATCH')<button class="planner-button planner-button-outline" type="submit" aria-label="{{ $item->done_at ? __('Mark :item not done', ['item' => $item->title]) : __('Mark :item done', ['item' => $item->title]) }}">{{ $item->done_at ? '✓' : '○' }}</button><span class="{{ $item->done_at ? 'is-done' : '' }}">{{ $item->title }}</span></form><form method="POST" action="{{ route('goals.items.destroy', [$goal, $item]) }}">@csrf @method('DELETE')<button class="planner-button planner-button-danger-quiet" type="submit">{{ __('Remove') }}</button></form></article>
            @endforeach
            <form class="planner-inline-form" method="POST" action="{{ route('goals.items.store', $goal) }}">@csrf<label class="planner-field"><span>{{ __('Add a checklist item') }}</span><input name="title" required maxlength="120"></label><button class="planner-button planner-button-outline" type="submit">{{ __('Add item') }}</button></form>
        </section>
    @endif

    @if ($goal)
        <section class="goal-link-tasks"><h2>{{ __('Linked routines') }}</h2><p class="planner-muted">{{ __('Choose this goal when adding or editing a routine.') }}</p><a class="planner-button planner-button-outline" href="{{ route('tasks.index') }}">{{ __('Manage task routines') }}</a></section>
        <form class="goal-delete-form" method="POST" action="{{ route('goals.destroy', $goal) }}" onsubmit="return confirm('{{ __('Remove this goal from future tracking? Past task logs will be kept.') }}')">@csrf @method('DELETE')<button class="planner-button planner-button-danger-quiet" type="submit">{{ __('Delete goal') }}</button></form>
    @endif
</x-layouts::app>
