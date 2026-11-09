<x-layouts::app :title="$project ? __('Edit project') : __('Add project')">
    <section class="page-heading"><p class="planner-muted"><a href="{{ route('goals.projects') }}">{{ __('Projects') }}</a></p><h1>{{ $project ? __('Edit project') : __('Add project') }}</h1></section>
    <x-goals-tabs active="projects" />
    <form class="planner-form-card" method="POST" action="{{ $project ? route('goals.projects.update', $project) : route('goals.projects.store') }}">
        @csrf @if ($project) @method('PUT') @endif
        <label class="planner-field"><span>{{ __('Project name') }}</span><input name="name" required maxlength="160" value="{{ old('name', $project?->name) }}">@error('name')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-field"><span>{{ __('Description') }}</span><textarea name="description" rows="3" maxlength="5000">{{ old('description', $project?->description) }}</textarea>@error('description')<small>{{ $message }}</small>@enderror</label>
        <label class="planner-field"><span>{{ __('Skills needed (one per line)') }}</span><textarea name="skills_needed" rows="3" maxlength="2000">{{ old('skills_needed', implode("\n", $project?->skills_needed ?? [])) }}</textarea>@error('skills_needed')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-row"><label class="planner-field"><span>{{ __('Status') }}</span><select name="status">@foreach (['idea'=>'Idea','planned'=>'Planned','active'=>'Active','done'=>'Done'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $project?->status ?? 'idea') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="planner-field"><span>{{ __('Priority (1 highest, 5 lowest)') }}</span><select name="priority">@foreach (range(1,5) as $priority)<option value="{{ $priority }}" @selected((int) old('priority', $project?->priority ?? 3) === $priority)>{{ $priority }}</option>@endforeach</select></label></div>
        <label class="planner-field"><span>{{ __('Private notes') }}</span><textarea name="notes" rows="5" maxlength="10000">{{ old('notes', $project?->notes) }}</textarea><small>{{ __('Project notes are encrypted before storage.') }}</small>@error('notes')<small>{{ $message }}</small>@enderror</label>
        <div class="planner-form-actions"><button class="planner-button planner-button-primary" type="submit">{{ $project ? __('Save project') : __('Add project') }}</button><a class="planner-button planner-button-outline" href="{{ route('goals.projects') }}">{{ __('Cancel') }}</a></div>
    </form>
    @if ($project)<form class="goal-delete-form" method="POST" action="{{ route('goals.projects.destroy', $project) }}" onsubmit="return confirm('{{ __('Archive this project?') }}')">@csrf @method('DELETE')<button class="planner-button planner-button-danger-quiet" type="submit">{{ __('Archive project') }}</button></form>@endif
</x-layouts::app>
