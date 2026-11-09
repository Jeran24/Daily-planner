<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
        $this->assertSame(6, $user->taskTemplates()->count());
        $scheduledToday = $user->taskTemplates()->whereJsonContains('weekdays', now($user->timezone)->dayOfWeek)->count();
        $this->assertSame($scheduledToday, $user->taskLogs()->whereDate('date', now($user->timezone)->toDateString())->count());
    }
}
