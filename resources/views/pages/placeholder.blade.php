<x-layouts::app :title="__($pageTitle)">
    <section class="page-heading"><p class="planner-muted">{{ __('Daily Planner') }}</p><h1>{{ __($pageTitle) }}</h1></section>
    <section class="planner-card planner-empty-card">
        <h2>{{ __('This section is ready for its next phase') }}</h2>
        <p>{{ __('The navigation shell is in place. This screen will be filled in during its planned phase.') }}</p>
    </section>
</x-layouts::app>
