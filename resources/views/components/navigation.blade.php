@props(['mobile' => false])
@php
    $links = [
        ['label' => __('Today'), 'route' => 'home', 'icon' => 'home', 'active' => request()->routeIs('home', 'dashboard')],
        ['label' => __('Calendar'), 'route' => 'calendar', 'icon' => 'calendar', 'active' => request()->routeIs('calendar')],
        ['label' => __('Goals'), 'route' => 'goals', 'icon' => 'goals', 'active' => request()->routeIs('goals', 'goals.*')],
        ['label' => __('Journal'), 'route' => 'journal', 'icon' => 'journal', 'active' => request()->routeIs('journal')],
        ['label' => __('Settings'), 'route' => 'profile.edit', 'icon' => 'settings', 'active' => request()->routeIs('profile.*', 'appearance.*', 'security.*', 'tasks.*', 'settings.*')],
    ];
@endphp
<nav {{ $attributes->class($mobile ? 'planner-tabs' : 'planner-rail-nav') }} aria-label="{{ __('Main navigation') }}">
    @foreach ($links as $link)
        <a href="{{ route($link['route']) }}" class="planner-nav-link {{ $link['active'] ? 'is-active' : '' }}"
            @if ($link['active']) aria-current="page" @endif aria-label="{{ $link['label'] }}" title="{{ $link['label'] }}">
            <span class="planner-nav-icon" aria-hidden="true">
                @switch($link['icon'])
                    @case('home')<svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>@break
                    @case('calendar')<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>@break
                    @case('goals')<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/></svg>@break
                    @case('journal')<svg viewBox="0 0 24 24"><path d="M6 4h13a2 2 0 0 1 2 2v15H8a2 2 0 0 1-2-2z"/><path d="M6 17a2 2 0 0 1 2-2h13M9 8h8M9 11h6"/></svg>@break
                    @case('settings')<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.3 1.3-1.4 2.4-1.8-.5a7.7 7.7 0 0 1-1.5.9l-.4 1.8h-2.8l-.4-1.8a7.7 7.7 0 0 1-1.5-.9l-1.8.5-1.4-2.4 1.3-1.3a7.2 7.2 0 0 1 0-1.8l-1.3-1.3 1.4-2.4 1.8.5a7.7 7.7 0 0 1 1.5-.9l.4-1.8h2.8l.4 1.8a7.7 7.7 0 0 1 1.5.9l1.8-.5 1.4 2.4-1.3 1.3a7.2 7.2 0 0 1 0 1.8z"/></svg>@break
                @endswitch
            </span>
            @if ($mobile)<span class="planner-nav-label">{{ $link['label'] }}</span>@else<span class="sr-only">{{ $link['label'] }}</span>@endif
        </a>
    @endforeach
</nav>
