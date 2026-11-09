<x-layouts::app :title="__('Reports')">
    <section class="page-heading"><p class="planner-muted">{{ __('Goals') }}</p><h1>{{ __('Reports') }}</h1></section>
    <x-goals-tabs active="reports" />
    <nav class="report-period-tabs" aria-label="{{ __('Report period') }}"><a href="{{ route('goals.reports', ['period' => 'week']) }}" @if ($period === 'week') aria-current="page" @endif>{{ __('Week') }}</a><a href="{{ route('goals.reports', ['period' => 'month']) }}" @if ($period === 'month') aria-current="page" @endif>{{ __('Month') }}</a></nav>
    <p class="planner-muted">{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }}</p>
    <section class="report-metrics" aria-label="{{ __('Summary') }}">
        <article class="planner-card"><span class="planner-card-kicker">{{ __('Completion') }}</span><strong>{{ $summary['percent'] === null ? '—' : number_format($summary['percent'], 0).'%' }}</strong><p>{{ $summary['done'] }} {{ __('done') }} · {{ $summary['minimum'] }} {{ __('minimum') }} · {{ $summary['skipped'] }} {{ __('skipped') }}</p></article>
        <article class="planner-card"><span class="planner-card-kicker">{{ __('Best day') }}</span><strong>{{ $bestDay ? number_format($bestDay['percent'], 0).'%' : '—' }}</strong><p>{{ $bestDay ? \Carbon\CarbonImmutable::parse($bestDay['date'])->format('D, M j') : __('No task history') }}</p></article>
        <article class="planner-card"><span class="planner-card-kicker">{{ __('Worst day') }}</span><strong>{{ $worstDay ? number_format($worstDay['percent'], 0).'%' : '—' }}</strong><p>{{ $worstDay ? \Carbon\CarbonImmutable::parse($worstDay['date'])->format('D, M j') : __('No task history') }}</p></article>
        <article class="planner-card"><span class="planner-card-kicker">{{ __('Current streak') }}</span><strong>{{ $streak }}</strong><p>{{ __('days at your threshold') }}</p></article>
        <article class="planner-card"><span class="planner-card-kicker">{{ __('Average mood') }}</span><strong>{{ $averageMood === null ? '—' : number_format($averageMood, 1).'/5' }}</strong><p>{{ __('From journal entries in this period') }}</p></article>
    </section>
    <section class="report-category-section"><h2>{{ __('Completed minutes by category') }}</h2>@if ($minutesByCategory->isEmpty())<article class="planner-card"><p>{{ __('No completed minutes recorded in this period yet.') }}</p></article>@else<div class="goal-list">@foreach ($minutesByCategory as $category => $minutes)<article class="planner-card report-category"><strong>{{ ucfirst($category) }}</strong><span>{{ number_format($minutes) }} {{ __('min') }}</span></article>@endforeach</div>@endif</section>
</x-layouts::app>
