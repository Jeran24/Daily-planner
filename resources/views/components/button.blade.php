@props(['variant' => 'primary', 'type' => 'button'])
<button type="{{ $type }}" {{ $attributes->class(['planner-button', 'planner-button-primary' => $variant === 'primary', 'planner-button-outline' => $variant === 'outline', 'planner-button-danger' => $variant === 'danger']) }}>{{ $slot }}</button>
