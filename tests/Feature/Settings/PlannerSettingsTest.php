<?php

namespace Tests\Feature\Settings;

use App\Models\TaskTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlannerSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_reminder_defaults_and_calendar_preferences_are_saved_and_used(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('settings.reminders.update'), [
            'remind_minutes_before' => 12, 'alarm_enabled' => 1,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('settings.goals.update'), [
            'week_starts_on' => 1, 'streak_threshold' => 75,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, (int) $user->fresh()->week_starts_on);
        $this->assertSame('75', $user->settings()->where('key', 'streak_threshold')->value('value'));

        $this->actingAs($user)->post(route('tasks.store'), [
            'name' => 'Walk', 'category' => 'health', 'start_time' => '08:00',
            'duration_minutes' => 30, 'weekdays' => [1, 3, 5], 'alarm_enabled' => 1,
        ])->assertSessionHasNoErrors();
        $task = $user->taskTemplates()->firstOrFail();
        $this->assertSame(12, $task->remind_minutes_before);
        $this->assertTrue($task->alarm_enabled);
    }

    public function test_idle_lock_requires_the_current_password_after_the_configured_inactivity(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 UTC');
        $user = User::factory()->create();
        $this->actingAs($user)->withSession([
            'planner_last_activity' => now('UTC')->subMinutes(11)->timestamp,
        ])->get(route('home'))->assertRedirect(route('lock.show'));

        $this->get(route('lock.show'))->assertOk()->assertSee('Planner locked');
        $this->post(route('lock.unlock'), ['password' => 'wrong-password-123'])->assertSessionHasErrors('password');
        $this->post(route('lock.unlock'), ['password' => 'planner-password-12'])->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk();
    }

    public function test_only_the_owner_can_revoke_their_other_session(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->get(route('settings.privacy'))->assertOk();
        $currentId = session()->getId();
        DB::table('sessions')->insert([
            ['id' => $currentId, 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'owner-other-session', 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'other-users-session', 'user_id' => $other->id, 'payload' => '', 'last_activity' => time()],
        ]);

        $this->get(route('settings.privacy'))->assertSee('owner-other-session')->assertDontSee('other-users-session');
        $this->delete(route('settings.devices.destroy', 'owner-other-session'))->assertRedirect(route('settings.privacy'));
        $this->assertDatabaseMissing('sessions', ['id' => 'owner-other-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-users-session', 'user_id' => $other->id]);
    }

    public function test_json_and_csv_exports_include_only_owner_data_and_decrypt_private_text(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->journalEntries()->create(['date' => now()->toDateString(), 'mood' => 4, 'body' => 'my private journal note']);
        $other->journalEntries()->create(['date' => now()->toDateString(), 'mood' => 2, 'body' => 'another person note']);
        $template = $owner->taskTemplates()->create([
            'name' => 'Confidential note task', 'category' => 'other', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => [0], 'is_active' => false,
        ]);
        $owner->taskLogs()->create([
            'task_template_id' => $template->id, 'date' => now()->toDateString(),
            'scheduled_at' => now('UTC'), 'status' => 'done', 'note' => 'owner-only task note',
        ]);

        $json = $this->actingAs($owner)->get(route('settings.export.json'))->assertDownload()->streamedContent();
        $this->assertStringContainsString('my private journal note', $json);
        $this->assertStringContainsString('owner-only task note', $json);
        $this->assertStringNotContainsString('another person note', $json);
        $this->assertStringNotContainsString($owner->password, $json);

        $csv = $this->get(route('settings.export.csv'))->assertDownload()->streamedContent();
        $this->assertStringContainsString('journal_entries', $csv);
        $this->assertStringContainsString('my private journal note', $csv);
        $this->assertStringNotContainsString('another person note', $csv);
    }

    public function test_account_deletion_requires_the_current_password_and_removes_the_owner_data(): void
    {
        $user = User::factory()->create();
        $user->journalEntries()->create(['date' => now()->toDateString(), 'mood' => 3, 'body' => 'delete with account']);

        $this->actingAs($user)->delete(route('settings.account.delete'), ['password' => 'incorrect-password-12'])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->delete(route('settings.account.delete'), ['password' => 'planner-password-12'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('journal_entries', ['user_id' => $user->id]);
        $this->assertGuest();
    }

    public function test_task_routines_can_be_reordered_and_missing_defaults_restored_without_deleting_history(): void
    {
        $user = User::factory()->create();
        $first = $this->makeTask($user, 'First', 0);
        $second = $this->makeTask($user, 'Second', 1);
        $log = $user->taskLogs()->create([
            'task_template_id' => $first->id, 'date' => now($user->timezone)->subDay()->toDateString(),
            'scheduled_at' => now('UTC')->subDay(), 'status' => 'done',
        ]);

        $this->actingAs($user)->withSession(['planner_last_activity' => now('UTC')->timestamp, 'planner_locked' => false])
            ->from(route('tasks.index'))->patch(route('tasks.move', $first), ['direction' => 'down'])->assertRedirect(route('tasks.index'));
        $this->assertSame([$second->id, $first->id], $user->taskTemplates()->orderBy('sort_order')->pluck('id')->all());

        $this->actingAs($user)->withSession(['planner_last_activity' => now('UTC')->timestamp, 'planner_locked' => false])->from(route('tasks.index'))
            ->from(route('tasks.index'))->post(route('tasks.reset-defaults'))->assertRedirect(route('tasks.index'));
        $this->assertSame(6, $user->taskTemplates()->where('is_default', true)->count());
        $this->assertNotNull($log->fresh());
    }

    private function makeTask(User $user, string $name, int $sort): TaskTemplate
    {
        return $user->taskTemplates()->create([
            'name' => $name, 'category' => 'other', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => range(0, 6), 'sort_order' => $sort, 'is_active' => false,
        ]);
    }
}
