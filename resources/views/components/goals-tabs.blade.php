@props(['active' => 'goals'])
<nav class="goals-tabs" aria-label="{{ __('Goals sections') }}">
    <a href="{{ route('goals') }}" @if ($active === 'goals') aria-current="page" @endif>{{ __('Goals') }}</a>
    <a href="{{ route('goals.projects') }}" @if ($active === 'projects') aria-current="page" @endif>{{ __('Projects') }}</a>
    <a href="{{ route('goals.reports') }}" @if ($active === 'reports') aria-current="page" @endif>{{ __('Reports') }}</a>
</nav>
