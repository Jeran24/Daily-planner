<x-layouts::app :title="__('Today')">
    <section class="page-heading planner-today-heading">
        <div>
            <p class="planner-muted">{{ now(auth()->user()->timezone)->format('D, j M Y') }}</p>
            <h1>{{ __('Today') }}</h1>
        </div>
        <a class="planner-button planner-button-outline" href="{{ route('tasks.create') }}">{{ __('Add custom task') }}</a>
    </section>

    <section class="planner-today-grid" aria-label="Today's plan">
        <article class="planner-card planner-next-card">
            <span class="planner-card-kicker">{{ __('Next task') }}</span>
            @if ($nextTask)
                <h2>{{ $nextTask->template?->name ?? __('Task') }}</h2>
                <p>{{ $nextTask->scheduled_at->setTimezone(auth()->user()->timezone)->format(auth()->user()->time_format === '12h' ? 'g:i A' : 'H:i') }} · {{ $nextTask->template?->duration_minutes ?? 60 }} {{ __('minutes planned') }}</p>
            @else
                <h2>{{ __('You’re all caught up') }}</h2>
                <p>{{ __('No pending tasks remain in today’s plan.') }}</p>
            @endif
            <a class="planner-text-link" href="{{ route('tasks.index') }}">{{ __('Manage routines') }} <span aria-hidden="true">→</span></a>
        </article>

        <article class="planner-card planner-summary-card">
            <span class="planner-card-kicker">{{ __('Daily progress') }}</span>
            <h2>{{ $summary['done'] + $summary['minimum'] }} <span>{{ __('of') }}</span> {{ $summary['total'] - $summary['skipped'] }}</h2>
            <p>{{ __('tasks completed') }}@if ($summary['minimum']) · {{ $summary['minimum'] }} {{ __('minimum') }}@endif</p>
            @if ($summary['percent'] !== null)
                <x-progress-bar :value="$summary['percent']" :label="__('Day completion')" />
                <p class="planner-muted">{{ number_format($summary['percent'], 0) }}% {{ __('complete') }}</p>
            @else
                <p class="planner-muted">{{ __('No tasks planned for today.') }}</p>
            @endif
            <p class="planner-streak"><span aria-hidden="true">✦</span> {{ $streak }} {{ __('day streak') }}</p>
        </article>
    </section>

    @if ($logs->isNotEmpty())
        <section class="planner-task-list-section" aria-labelledby="today-tasks-heading">
            <div class="planner-section-heading">
                <div><span class="planner-card-kicker">{{ __('Your routine') }}</span><h2 id="today-tasks-heading">{{ __('Today’s tasks') }}</h2></div>
                <span class="planner-muted">{{ $logs->count() }} {{ __('planned') }}</span>
            </div>
            <div class="planner-task-list">
                @foreach ($logs as $log)
                    <article class="planner-task-item status-{{ $log->status }}">
                        <div class="planner-task-item-top">
                            <span class="planner-task-status" aria-label="{{ ucfirst($log->status) }}"></span>
                            <div class="planner-task-item-copy">
                                <h3>{{ $log->template?->name ?? __('Task') }}</h3>
                                <p>{{ $log->scheduled_at->setTimezone(auth()->user()->timezone)->format(auth()->user()->time_format === '12h' ? 'g:i A' : 'H:i') }} · {{ ucfirst($log->template?->category ?? 'other') }} · {{ $log->template?->duration_minutes ?? 60 }} {{ __('min') }}</p>
                            </div>
                            <span class="planner-task-state">{{ ucfirst($log->status) }}</span>
                        </div>
                        @if ($log->status !== 'skipped')
                            <div class="planner-task-actions">
                                @if ($log->status !== 'done')
                                    <form method="POST" action="{{ route('task-logs.status', $log) }}">@csrf<input type="hidden" name="status" value="done"><button class="planner-button planner-button-primary" type="submit">{{ __('Done') }}</button></form>
                                @endif
                                @if ($log->template?->minimum_version && $log->status !== 'minimum')
                                    <form method="POST" action="{{ route('task-logs.status', $log) }}">@csrf<input type="hidden" name="status" value="minimum"><button class="planner-button planner-button-outline" type="submit">{{ __('Did the minimum') }}</button></form>
                                @endif
                                @if ($log->status === 'pending')
                                    <form method="POST" action="{{ route('task-logs.status', $log) }}">@csrf<input type="hidden" name="status" value="skipped"><button class="planner-button planner-button-quiet" type="submit">{{ __('Skip') }}</button></form>
                                @else
                                    <form method="POST" action="{{ route('task-logs.status', $log) }}">@csrf<input type="hidden" name="status" value="pending"><button class="planner-button planner-button-quiet" type="submit">{{ __('Undo') }}</button></form>
                                @endif
                            </div>
                        @else
                            <form method="POST" action="{{ route('task-logs.status', $log) }}">@csrf<input type="hidden" name="status" value="pending"><button class="planner-button planner-button-quiet" type="submit">{{ __('Undo skip') }}</button></form>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @else
        <section class="planner-card planner-empty-card">
            <span class="planner-card-kicker">{{ __('Your daily plan') }}</span>
            <h2>{{ __('No tasks planned today') }}</h2>
            <p>{{ __('Add a task routine to start building your plan.') }}</p>
            <a class="planner-button planner-button-primary" href="{{ route('tasks.create') }}">{{ __('Add a task') }}</a>
        </section>
    @endif
</x-layouts::app>
