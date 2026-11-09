<?php

namespace Tests\Feature\Reminders;

use App\Models\TaskTemplate;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PlannerRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_due_reminders_are_limited_to_the_owner_and_pending_logs(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 18:55:00 UTC');
        $owner = User::factory()->create(['timezone' => 'UTC']);
        $other = User::factory()->create(['timezone' => 'UTC']);
        $task = $this->makeTask($owner);
        $doneTask = $this->makeTask($owner);
        $otherTask = $this->makeTask($other);
        $this->makeLog($owner, $task, 'pending', '2026-11-08 19:00:00 UTC');
        $this->makeLog($owner, $doneTask, 'done', '2026-11-08 19:00:00 UTC');
        $this->makeLog($other, $otherTask, 'pending', '2026-11-08 19:00:00 UTC');

        $this->actingAs($owner)->getJson(route('reminders.due'))->assertOk()
            ->assertJsonPath('reminders.0.name', 'Study session')->assertJsonCount(1, 'reminders');
    }

    public function test_scheduler_sends_one_push_for_due_pending_work_and_never_for_completed_work(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 18:55:00 UTC');
        Notification::fake();
        $user = User::factory()->create(['timezone' => 'UTC']);
        $task = $this->makeTask($user);
        $user->pushSubscriptions()->create([
            'endpoint' => 'https://push.example.test/subscription/1',
            'public_key' => 'public-key', 'auth_token' => 'auth-token', 'content_encoding' => 'aes128gcm',
            'device_label' => 'Test browser',
        ]);
        $pending = $this->makeLog($user, $task, 'pending', '2026-11-08 19:00:00 UTC');
        $this->makeLog($user, $task, 'done', '2026-11-07 19:00:00 UTC', '2026-11-07');

        $this->artisan('planner:send-reminders')->assertSuccessful();
        $this->artisan('planner:send-reminders')->assertSuccessful();
        Notification::assertSentToTimes($user, TaskReminderNotification::class, 1);
        $this->assertDatabaseHas('reminder_events', ['task_log_id' => $pending->id, 'kind' => 'push']);
        $this->assertSame(1, $user->pushSubscriptions()->count());
    }

    public function test_editing_a_routine_moves_its_pending_today_reminder_without_changing_history(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 UTC');
        $user = User::factory()->create(['timezone' => 'UTC']);
        $task = $this->makeTask($user);
        $today = $user->taskLogs()->create([
            'task_template_id' => $task->id, 'date' => '2026-11-08',
            'scheduled_at' => '2026-11-08 19:00:00', 'status' => 'pending',
        ]);
        $past = $user->taskLogs()->create([
            'task_template_id' => $task->id, 'date' => '2026-11-07',
            'scheduled_at' => '2026-11-07 19:00:00', 'status' => 'done',
        ]);

        $this->actingAs($user)->put(route('tasks.update', $task), [
            'name' => $task->name, 'category' => 'study', 'start_time' => '19:30',
            'duration_minutes' => 60, 'weekdays' => range(0, 6), 'remind_minutes_before' => 5,
        ])->assertRedirect(route('tasks.index'));

        $this->assertSame('2026-11-08 19:30:00', $today->fresh()->scheduled_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-07 19:00:00', $past->fresh()->scheduled_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_push_subscription_is_created_for_only_the_logged_in_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $subscription = [
            'endpoint' => 'https://push.example.test/subscription/owner',
            'keys' => ['p256dh' => 'owner-public-key', 'auth' => 'owner-auth-token'],
            'device_label' => 'My browser',
        ];

        $this->actingAs($owner)->postJson(route('reminders.subscribe'), $subscription)->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, $owner->pushSubscriptions()->count());
        $this->assertSame(0, $other->pushSubscriptions()->count());
    }

    public function test_manifest_and_service_worker_are_shipped(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertFileExists(public_path('service-worker.js'));
        $this->assertFileExists(public_path('icons/planner-192.png'));
        $this->assertFileExists(public_path('icons/planner-512.png'));
    }

    private function makeTask(User $user): TaskTemplate
    {
        return $user->taskTemplates()->create([
            'name' => 'Study session', 'category' => 'study', 'start_time' => '19:00',
            'duration_minutes' => 60, 'weekdays' => range(0, 6), 'is_active' => true,
            'remind_minutes_before' => 5,
        ]);
    }

    private function makeLog(User $user, TaskTemplate $task, string $status, string $scheduledAt, ?string $date = null)
    {
        return $user->taskLogs()->create([
            'task_template_id' => $task->id,
            'date' => $date ?? substr($scheduledAt, 0, 10),
            'scheduled_at' => $scheduledAt,
            'status' => $status,
        ]);
    }
}
