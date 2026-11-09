@props(['value' => 0, 'plan' => null, 'label' => null])
<div class="planner-progress" role="progressbar" aria-valuenow="{{ min(100, max(0, $value)) }}" aria-valuemin="0" aria-valuemax="100" @if ($label) aria-label="{{ $label }}" @endif>
    <span class="planner-progress-fill" style="width: {{ min(100, max(0, $value)) }}%"></span>
    @if (! is_null($plan))<span class="planner-progress-plan" style="left: {{ min(100, max(0, $plan)) }}%" aria-label="{{ __('Plan') }}"></span>@endif
</div>
