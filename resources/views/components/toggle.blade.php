@props(['label', 'checked' => false, 'name' => null])
<label class="planner-toggle-row">
    <span>{{ $label }}</span>
    <input type="checkbox" role="switch" @if ($name) name="{{ $name }}" @endif @checked($checked) {{ $attributes }}>
    <span class="planner-toggle-track" aria-hidden="true"></span>
</label>
