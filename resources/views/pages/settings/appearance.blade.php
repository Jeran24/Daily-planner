<?php

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component {
    public string $theme = 'system';

    public function mount(): void
    {
        $this->theme = auth()->user()->theme;
    }

    public function updatedTheme(string $theme): void
    {
        $validated = validator(['theme' => $theme], [
            'theme' => ['required', 'in:light,dark,system'],
        ])->validate();

        auth()->user()->forceFill(['theme' => $validated['theme']])->save();

        $this->dispatch('theme-updated', theme: $validated['theme']);
        Flux::toast(variant: 'success', text: __('Appearance saved.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')
    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Choose Light, Dark, or Follow device.')">
        <fieldset class="theme-options">
            <legend class="planner-label">{{ __('Theme') }}</legend>
            @foreach (['light' => __('Light'), 'dark' => __('Dark'), 'system' => __('Follow device')] as $value => $label)
                <label class="theme-option">
                    <input type="radio" name="theme" value="{{ $value }}" wire:model.live="theme" @checked($theme === $value)>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </fieldset>
    </x-pages::settings.layout>
</section>
