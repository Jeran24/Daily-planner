<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_theme_choice_is_saved_for_the_signed_in_owner(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::settings.appearance')
            ->set('theme', 'dark')
            ->assertHasNoErrors();

        $this->assertSame('dark', $user->fresh()->theme);

        Livewire::test('pages::settings.appearance')
            ->set('theme', 'light')
            ->assertHasNoErrors();

        $this->assertSame('light', $user->fresh()->theme);
    }

    public function test_invalid_theme_choice_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::settings.appearance')
            ->set('theme', 'sepia')
            ->assertHasErrors('theme');

        $this->assertSame('system', $user->fresh()->theme);
    }

    public function test_main_navigation_pages_require_authentication(): void
    {
        foreach (['home', 'calendar', 'goals', 'journal', 'profile.edit'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }
}
