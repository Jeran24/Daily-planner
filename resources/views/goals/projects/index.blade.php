<x-layouts::app :title="__('Projects')">
    <section class="page-heading goals-heading"><div><p class="planner-muted">{{ __('Goals') }}</p><h1>{{ __('Projects') }}</h1></div><a class="planner-button planner-button-primary" href="{{ route('goals.projects.create') }}">{{ __('Add project') }}</a></section>
    <x-goals-tabs active="projects" />
    @if (session('status'))<p class="planner-flash" role="status">{{ session('status') }}</p>@endif
    @if ($projects->isEmpty())
        <section class="planner-card planner-empty-card"><h2>{{ __('No projects yet') }}</h2><p>{{ __('Keep project ideas, skills, and notes together here.') }}</p><a class="planner-button planner-button-primary" href="{{ route('goals.projects.create') }}">{{ __('Add project') }}</a></section>
    @else
        <div class="goal-list">
            @foreach ($projects as $project)
                <article class="planner-card project-card"><div class="goal-card-heading"><div><span class="planner-card-kicker">{{ ucfirst($project->status) }} · {{ __('Priority') }} {{ $project->priority }}/5</span><h2>{{ $project->name }}</h2></div><a class="planner-button planner-button-outline" href="{{ route('goals.projects.edit', $project) }}">{{ __('Edit') }}</a></div>
                    @if ($project->description)<p>{{ $project->description }}</p>@endif
                    @if ($project->skills_needed)<p><strong>{{ __('Skills') }}:</strong> {{ implode(', ', $project->skills_needed) }}</p>@endif
                    @if ($project->notes)<details><summary>{{ __('Private notes') }}</summary><p class="project-notes">{{ $project->notes }}</p></details>@endif
                </article>
            @endforeach
        </div>
    @endif
</x-layouts::app>
