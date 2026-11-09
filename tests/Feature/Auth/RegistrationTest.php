<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'planner-password-12',
            'password_confirmation' => 'planner-password-12',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('home', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_first_account_gets_owner_defaults_and_registration_then_closes(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'planner-password-12',
            'password_confirmation' => 'planner-password-12',
        ])->assertSessionHasNoErrors();

        $owner = User::query()->sole();
        $this->assertSame('Africa/Kigali', $owner->timezone);
        $this->assertSame(0, $owner->week_starts_on);
        $this->assertSame('system', $owner->theme);
        $this->assertSame('24h', $owner->time_format);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->get(route('register'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertDontSee('Create the owner account');

        $this->post(route('register.store'), [
            'name' => 'Second',
            'email' => 'second@example.com',
            'password' => 'planner-password-12',
            'password_confirmation' => 'planner-password-12',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->count());
    }

    public function test_registration_rejects_passwords_shorter_than_twelve_characters(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'short-pass',
            'password_confirmation' => 'short-pass',
        ])->assertSessionHasErrors('password');

        $this->assertSame(0, User::query()->count());
    }
}
