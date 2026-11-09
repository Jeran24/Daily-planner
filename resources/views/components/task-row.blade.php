@props(['title', 'time', 'status' => 'pending'])
<div {{ $attributes->class('planner-task-row') }}>
    <span class="planner-task-status" aria-hidden="true"></span>
    <span class="planner-task-title">{{ $title }}</span>
    <span class="planner-muted">{{ $time }}</span>
</div>
