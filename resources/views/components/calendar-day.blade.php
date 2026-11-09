@props(['number', 'status' => 'empty', 'today' => false, 'future' => false, 'empty' => false, 'taskCount' => 0, 'href' => null, 'label' => null])
@php($classes = ['planner-calendar-day', 'is-today' => $today, 'is-future' => $future, 'is-empty' => $empty, 'status-'.$status => in_array($status, ['green', 'yellow', 'orange', 'red'])])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} aria-label="{{ $label }}" title="{{ $label }}">
        {{ $number }}@if ($taskCount > 0)<i class="planner-day-marker" aria-hidden="true"></i>@endif
    </a>
@else
    <span {{ $attributes->class($classes) }} aria-label="{{ $label }}" title="{{ $label }}">
        {{ $number }}@if ($taskCount > 0)<i class="planner-day-marker" aria-hidden="true"></i>@endif
    </span>
@endif
