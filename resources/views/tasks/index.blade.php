<x-layouts::app :title="__('Tasks and routines')">
    <section class="page-heading planner-today-heading">
        <div><p class="planner-muted">{{ __('Settings') }}</p><h1>{{ __('Tasks and routines') }}</h1></div>
        <div class="planner-form-actions"><a class="planner-button planner-button-primary" href="{{ route('tasks.create') }}">{{ __('Add custom task') }}</a><form method="POST" action="{{ route('tasks.reset-defaults') }}" onsubmit="return confirm('{{ __('Add any missing default routines? Existing logs will be kept.') }}')">@csrf<button class="planner-button planner-button-outline" type="submit">{{ __('Restore defaults') }}</button></form></div>
    </section>
    <x-settings-navigation />
    @if (session('status')) <p class="planner-flash" role="status">{{ session('status') }}</p> @endif
    <p class="planner-page-intro">{{ __('Your routines create a task for each scheduled day. Editing a routine never rewrites your past days.') }}</p>
    @if ($tasks->isEmpty())
        <section class="planner-card planner-empty-card"><h2>{{ __('No routines yet') }}</h2><p>{{ __('Add your first task routine to build your daily plan.') }}</p></section>
    @else
        <div class="planner-task-list planner-routine-list">
            @foreach ($tasks as $task)
                <article class="planner-task-item">
                    <div class="planner-task-item-top">
                        <span class="planner-task-status {{ $task->is_active ? 'is-active' : '' }}" aria-hidden="true"></span>
                        <div class="planner-task-item-copy"><h2>{{ $task->name }}</h2><p>{{ substr($task->start_time, 0, 5) }} · {{ ucfirst($task->category) }} · {{ $task->duration_minutes }} {{ __('min') }} · {{ collect($task->weekdays)->map(fn ($day) => ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$day])->join(', ') }}@if ($task->goal) · {{ __('Goal') }}: {{ $task->goal->title }}@endif</p></div>
                        <span class="planner-task-state">{{ $task->is_active ? __('On') : __('Off') }}</span>
                    </div>
                    <div class="planner-task-actions">
                        @if (! $loop->first)<form method="POST" action="{{ route('tasks.move', $task) }}">@csrf @method('PATCH')<input type="hidden" name="direction" value="up"><button class="planner-button planner-button-quiet" type="submit">{{ __('Move up') }}</button></form>@endif
                        @if (! $loop->last)<form method="POST" action="{{ route('tasks.move', $task) }}">@csrf @method('PATCH')<input type="hidden" name="direction" value="down"><button class="planner-button planner-button-quiet" type="submit">{{ __('Move down') }}</button></form>@endif
                        <a class="planner-button planner-button-outline" href="{{ route('tasks.edit', $task) }}">{{ __('Edit') }}</a>
                        <form method="POST" action="{{ route('tasks.toggle', $task) }}">@csrf @method('PATCH')<button class="planner-button planner-button-quiet" type="submit">{{ $task->is_active ? __('Turn off') : __('Turn on') }}</button></form>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('{{ __('Remove this routine from future days? Past history will be kept.') }}')">@csrf @method('DELETE')<button class="planner-button planner-button-danger-quiet" type="submit">{{ __('Delete') }}</button></form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts::app>
