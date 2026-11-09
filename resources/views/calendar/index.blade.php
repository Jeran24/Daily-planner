<x-layouts::app :title="__('Calendar')">
    <section class="page-heading calendar-heading">
        <div><p class="planner-muted">{{ __('Your history') }}</p><h1>{{ __('Calendar') }}</h1></div>
        <div class="calendar-month-controls">
            <a class="planner-button planner-button-outline" href="{{ route('calendar', ['month' => $month->subMonth()->format('Y-m')]) }}" aria-label="{{ __('Previous month') }}">‹</a>
            <h2>{{ $month->format('F Y') }}</h2>
            <a class="planner-button planner-button-outline" href="{{ route('calendar', ['month' => $month->addMonth()->format('Y-m')]) }}" aria-label="{{ __('Next month') }}">›</a>
        </div>
    </section>

    <section class="planner-card calendar-card" aria-label="{{ $month->format('F Y') }}">
        <div class="calendar-grid calendar-weekdays" aria-hidden="true">
            @foreach ($weekdays as $weekday)<span>{{ $weekday }}</span>@endforeach
        </div>
        <div class="calendar-grid calendar-days">
            @for ($blank = 0; $blank < $leadingBlanks; $blank++)<span class="calendar-blank" aria-hidden="true"></span>@endfor
            @foreach ($days as $day)
                @php
                    $date = $day['date'];
                    $count = $day['summary']['total'];
                    $status = $day['color'] ?? 'empty';
                    $label = $date->format('l, F j').', '.($count ? $count.' '.__('tasks') : __('no tasks')).', '.($day['color'] ?? __('no completion recorded'));
                @endphp
                <x-calendar-day
                    :number="$date->day"
                    :status="$status"
                    :today="$day['isToday']"
                    :future="$day['isFuture']"
                    :empty="$count === 0"
                    :task-count="$count"
                    :label="$label"
                    :href="route('calendar', ['month' => $month->format('Y-m'), 'day' => $date->toDateString()])"
                />
            @endforeach
        </div>
        <div class="calendar-legend" aria-label="{{ __('Calendar completion colors') }}">
            <span><i class="legend-green"></i>80%+</span><span><i class="legend-yellow"></i>50–79%</span><span><i class="legend-orange"></i>1–49%</span><span><i class="legend-red"></i>0%</span>
        </div>
    </section>

    @if ($selectedDate)
        <section class="calendar-day-detail" aria-labelledby="calendar-day-title">
        <div class="planner-section-heading"><div><span class="planner-card-kicker">{{ __('Day detail') }}</span><h2 id="calendar-day-title">{{ $selectedDate->format('l, F j, Y') }}</h2></div>@if ($selectedDate->lessThanOrEqualTo(now(auth()->user()->timezone)->startOfDay()))<a class="planner-button planner-button-outline" href="{{ route('journal', ['date' => $selectedDate->toDateString()]) }}">{{ $journal ? __('View journal') : __('Write journal') }}</a>@endif</div>
            @if ($selectedSummary && $selectedSummary['percent'] !== null)
                <p class="planner-muted">{{ $selectedSummary['done'] }} {{ __('done') }} · {{ $selectedSummary['minimum'] }} {{ __('minimum') }} · {{ $selectedSummary['skipped'] }} {{ __('skipped') }} · {{ number_format($selectedSummary['percent'], 0) }}%</p>
            @endif
            @if ($selectedLogs->isEmpty())
                <article class="planner-card planner-empty-card"><h3>{{ __('No task history for this day') }}</h3><p>{{ __('There were no saved task logs on this date.') }}</p></article>
            @else
                <div class="planner-task-list">
                    @foreach ($selectedLogs as $log)
                        <article class="planner-task-item status-{{ $log->status }}"><div class="planner-task-item-top"><span class="planner-task-status" aria-hidden="true"></span><div class="planner-task-item-copy"><h3>{{ $log->template?->name ?? __('Task') }}</h3><p>{{ $log->scheduled_at->setTimezone(auth()->user()->timezone)->format(auth()->user()->time_format === '12h' ? 'g:i A' : 'H:i') }} · {{ ucfirst($log->template?->category ?? 'other') }}</p></div><span class="planner-task-state">{{ ucfirst($log->status) }}</span></div></article>
                    @endforeach
                </div>
            @endif
            @if ($journal)
                <article class="planner-card calendar-journal"><span class="planner-card-kicker">{{ __('Journal') }} · {{ __('Mood') }} {{ $journal->mood }}/5</span><p>{{ $journal->body }}</p></article>
            @endif
        </section>
    @endif
</x-layouts::app>
