@php
    $links = [
        ['label' => __('Profile'), 'route' => 'profile.edit', 'patterns' => ['profile.*']],
        ['label' => __('Security'), 'route' => 'security.edit', 'patterns' => ['security.*']],
        ['label' => __('Appearance'), 'route' => 'appearance.edit', 'patterns' => ['appearance.*']],
        ['label' => __('Tasks and routines'), 'route' => 'tasks.index', 'patterns' => ['tasks.*']],
        ['label' => __('Reminders and alarms'), 'route' => 'settings.reminders', 'patterns' => ['settings.reminders*']],
        ['label' => __('Goals and calendar'), 'route' => 'settings.goals', 'patterns' => ['settings.goals*']],
        ['label' => __('Privacy and security'), 'route' => 'settings.privacy', 'patterns' => ['settings.privacy*', 'settings.devices.*']],
        ['label' => __('Data and backup'), 'route' => 'settings.data', 'patterns' => ['settings.data', 'settings.export.*']],
    ];
@endphp
<nav class="planner-settings-nav" aria-label="{{ __('Settings sections') }}">
    @foreach ($links as $link)
        <a href="{{ route($link['route']) }}" @if (request()->routeIs(...$link['patterns'])) aria-current="page" @endif>{{ $link['label'] }}</a>
    @endforeach
</nav>
