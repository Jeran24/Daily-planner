<x-layouts::app :title="__('Goals')">
    <section class="page-heading goals-heading"><div><p class="planner-muted">{{ __('Build progress over time') }}</p><h1>{{ __('Goals') }}</h1></div><a class="planner-button planner-button-primary" href="{{ route('goals.create') }}">{{ __('Add goal') }}</a></section>
    <x-goals-tabs active="goals" />
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    @if ($goals->isEmpty())
        <section class="planner-card planner-empty-card"><span class="planner-card-kicker">{{ __('Start with one thing') }}</span><h2>{{ __('No goals yet') }}</h2><p>{{ __('Create a goal and choose how you want to measure it.') }}</p><a class="planner-button planner-button-primary" href="{{ route('goals.create') }}">{{ __('Create a goal') }}</a></section>
    @else
        <div class="goal-list">
            @foreach ($goals as $goal)
                @php
                    $report = $goal->report;
                    $statusLabel = match ($report['status']) {'completed' => __('Completed'), 'on_track' => __('On track'), 'slightly_behind' => __('Slightly behind'), default => __('At risk')};
                    $measureLabel = match ($goal->measure_type) {'checklist' => __('Checklist'), 'minutes' => __('Minutes'), 'task_count' => __('Task count'), default => __('Manual')};
                @endphp
                <article class="planner-card goal-card">
                    <div class="goal-card-heading"><div><span class="planner-card-kicker">{{ $measureLabel }} · {{ ucfirst($goal->status) }}</span><h2>{{ $goal->title }}</h2></div><a class="planner-button planner-button-outline" href="{{ route('goals.edit', $goal) }}">{{ __('Edit') }}</a></div>
                    <div class="goal-progress-line"><strong>{{ number_format($report['percent'], 0) }}%</strong><span>{{ $statusLabel }}</span><span class="planner-muted">{{ __('Plan') }} {{ number_format($report['plan'], 0) }}%</span></div>
                    <x-progress-bar :value="$report['percent']" :plan="$report['plan']" :label="__('Goal progress with plan marker')" />
                    @if ($goal->measure_type === 'checklist')<p class="planner-muted">{{ $report['done'] }} {{ __('of') }} {{ $report['total'] }} {{ __('items done') }}</p>@elseif (in_array($goal->measure_type, ['minutes', 'task_count']))<p class="planner-muted">{{ number_format($report['done']) }} {{ __('of') }} {{ number_format($report['total']) }} {{ $goal->measure_type === 'minutes' ? __('minutes logged') : __('tasks completed') }}</p>@endif
                    <p class="planner-muted">{{ __('Deadline') }} · {{ $goal->end_date->format('M j, Y') }}</p>
                    @if ($report['feasibility']['unrealistic'])
                        <aside class="goal-feasibility" role="status"><strong>{{ __('Unrealistic at your current pace') }}</strong>
                            @if ($report['feasibility']['finish_date'])<p>{{ __('At the recent pace, this goal would finish around :date.', ['date' => \Carbon\CarbonImmutable::parse($report['feasibility']['finish_date'])->format('M j, Y')]) }}</p>@else<p>{{ __('There is not enough recent progress to estimate a finish date yet.') }}</p>@endif
                            <div><a class="planner-button planner-button-outline" href="{{ route('goals.edit', $goal) }}#end-date">{{ __('Move deadline') }}</a><a class="planner-button planner-button-outline" href="{{ route('goals.edit', $goal) }}#target-value">{{ __('Cut scope') }}</a></div>
                        </aside>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</x-layouts::app>
