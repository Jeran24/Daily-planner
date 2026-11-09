<?php

namespace Tests\Feature\Tasks;

use App\Models\TaskLog;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\TaskPlannerService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskPlannerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_default_routines_create_one_log_per_scheduled_local_day_and_do_not_rewrite_past_days(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $planner = app(TaskPlannerService::class);
        $planner->seedDefaults($user);

        $todayLogs = $planner->ensureDay($user, '2026-11-08');
        $this->assertCount(6, $todayLogs);
        $this->assertSame(6, $planner->ensureDay($user, '2026-11-08')->count());
        $this->assertSame(6, $user->taskTemplates()->count());
        $this->assertSame(6, $user->taskLogs()->whereDate('date', '2026-11-08')->count());

        $read = $user->taskTemplates()->where('name', 'Read Bible')->firstOrFail();
        $pastLog = $user->taskLogs()->where('task_template_id', $read->id)->firstOrFail();
        $scheduledBefore = $pastLog->scheduled_at->toISOString();
        $read->update(['start_time' => '08:30']);

        $this->assertSame($scheduledBefore, $pastLog->fresh()->scheduled_at->toISOString());
        $this->assertSame('08:30', $read->refresh()->start_time);

        $saturday = $planner->ensureDay($user, '2026-11-14');
        $this->assertCount(2, $saturday);
        $this->assertSame(['Health care', 'Read Bible'], $saturday->pluck('template.name')->sort()->values()->all());
    }

    public function test_completion_minimum_skip_and_undo_follow_day_percentage_rule(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 06:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $planner = app(TaskPlannerService::class);
        $planner->seedDefaults($user);
        $logs = $planner->ensureDay($user, '2026-11-08');

        $planner->updateStatus($user, $logs[0], 'done');
        $planner->updateStatus($user, $logs[1], 'minimum');
        $planner->updateStatus($user, $logs[2], 'skipped');
        $summary = $planner->summary($user, '2026-11-08');

        $this->assertSame(6, $summary['total']);
        $this->assertSame(1, $summary['done']);
        $this->assertSame(1, $summary['minimum']);
        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(30.0, $summary['percent']);

        $planner->updateStatus($user, $logs[2], 'pending');
        $this->assertSame(25.0, $planner->summary($user, '2026-11-08')['percent']);
        $this->assertNull($logs[2]->fresh()->completed_at);
    }

    public function test_empty_days_have_no_percentage_and_calendar_color_uses_required_bands(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $planner = app(TaskPlannerService::class);
        $this->assertNull($planner->summary($user, '2026-11-08')['percent']);
        $this->assertNull($planner->color($user, '2026-11-08'));

        foreach ([
            '2026-11-08' => ['done', 'done', 'done', 'done', 'minimum'], // 90%
            '2026-11-07' => ['done'], // 100%
            '2026-11-06' => ['skipped'], // no denominator
            '2026-11-05' => ['pending'], // 0%
        ] as $date => $statuses) {
            foreach ($statuses as $index => $status) {
                $template = $this->makeTemplate($user, "{$date} {$index}");
                $this->makeLog($user, $template, $date, $status);
            }
        }

        $this->assertSame('green', $planner->color($user, '2026-11-08'));
        $this->assertSame('green', $planner->color($user, '2026-11-07'));
        $this->assertNull($planner->color($user, '2026-11-06'));
        $this->assertSame('red', $planner->color($user, '2026-11-05'));

        $yellow = $this->makeTemplate($user, 'Yellow task');
        $this->makeLog($user, $yellow, '2026-11-04', 'minimum');
        $this->assertSame('yellow', $planner->color($user, '2026-11-04'));

        $this->makeLog($user, $this->makeTemplate($user, 'Orange 1'), '2026-11-03', 'minimum');
        $this->makeLog($user, $this->makeTemplate($user, 'Orange 2'), '2026-11-03', 'pending');
        $this->makeLog($user, $this->makeTemplate($user, 'Orange 3'), '2026-11-03', 'pending');
        $this->assertSame('orange', $planner->color($user, '2026-11-03'));
    }

    public function test_pending_logs_become_missed_when_a_past_day_is_opened_and_today_does_not_break_streak(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $planner = app(TaskPlannerService::class);
        $template = $this->makeTemplate($user, 'Daily');
        foreach (['2026-11-05', '2026-11-07'] as $date) {
            $this->makeLog($user, $template, $date, 'done');
        }
        $this->makeLog($user, $template, '2026-11-04', 'pending');
        $this->makeLog($user, $template, '2026-11-08', 'pending');

        $planner->ensureDay($user, '2026-11-04');
        $this->assertSame('missed', $user->taskLogs()->whereDate('date', '2026-11-04')->firstOrFail()->status);
        $this->assertSame(2, $planner->streak($user));

        $planner->updateStatus($user, $user->taskLogs()->whereDate('date', '2026-11-08')->firstOrFail(), 'pending');
        $this->assertSame(2, $planner->streak($user));
    }

    public function test_reminder_time_is_calculated_in_the_user_timezone_then_stored_as_utc(): void
    {
        $user = User::factory()->create(['timezone' => 'Africa/Kigali']);
        $template = $this->makeTemplate($user, 'Evening', ['start_time' => '19:00:00', 'remind_minutes_before' => 5]);

        $reminder = app(TaskPlannerService::class)->reminderAt($user, $template, '2026-11-08');

        $this->assertSame('2026-11-08T16:55:00+00:00', $reminder->toIso8601String());
    }

    public function test_task_and_log_queries_are_limited_to_the_signed_in_users_rows(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $template = $this->makeTemplate($other, 'Private task');
        $log = $this->makeLog($other, $template, now()->toDateString(), 'pending');

        $this->actingAs($owner)->get(route('tasks.edit', $template->id))->assertNotFound();
        $this->actingAs($owner)->post(route('task-logs.status', $log->id), ['status' => 'done'])->assertNotFound();
        $this->actingAs($owner)->get(route('tasks.index'))->assertDontSee('Private task');
    }

    public function test_deleting_a_routine_keeps_past_logs_and_removes_only_future_logs(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $template = $this->makeTemplate($user, 'Keep my history');
        $template->update(['start_time' => '11:00:00']);
        $past = $this->makeLog($user, $template, '2026-11-07', 'done');
        $todayFuture = $this->makeLog($user, $template, '2026-11-08', 'pending');
        $tomorrow = $this->makeLog($user, $template, '2026-11-09', 'pending');

        $this->actingAs($user)->delete(route('tasks.destroy', $template->id))->assertRedirect(route('tasks.index'));

        $this->assertNotNull($past->fresh());
        $this->assertNull($todayFuture->fresh());
        $this->assertNull($tomorrow->fresh());
        $this->assertSoftDeleted('task_templates', ['id' => $template->id]);
    }

    public function test_task_log_notes_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();
        $template = $this->makeTemplate($user, 'Private note task');
        $log = $this->makeLog($user, $template, now()->toDateString(), 'done');
        $log->update(['note' => 'private journal style task note']);

        $stored = DB::table('task_logs')->where('id', $log->id)->value('note');
        $this->assertStringNotContainsString('private journal style task note', $stored);
        $this->assertSame('private journal style task note', $log->fresh()->note);
    }

    public function test_custom_task_form_validates_and_saves_user_owned_routine(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('tasks.store'), [
            'name' => 'Walk', 'category' => 'health', 'start_time' => '08:15', 'duration_minutes' => 30,
            'weekdays' => [1, 3, 5], 'minimum_version' => 'Five minutes outside',
        ])->assertSessionHasNoErrors()->assertRedirect(route('tasks.index'));

        $task = $user->taskTemplates()->sole();
        $this->assertSame('Walk', $task->name);
        $this->assertSame([1, 3, 5], $task->weekdays);

        $this->actingAs($user)->post(route('tasks.store'), [
            'name' => '', 'category' => 'wrong', 'start_time' => 'nonsense', 'duration_minutes' => 0, 'weekdays' => [],
        ])->assertSessionHasErrors(['name', 'category', 'start_time', 'duration_minutes', 'weekdays']);
    }

    private function makeTemplate(User $user, string $name, array $attributes = []): TaskTemplate
    {
        return $user->taskTemplates()->create(array_merge([
            'name' => $name, 'category' => 'other', 'start_time' => '09:00:00', 'duration_minutes' => 60,
            'weekdays' => range(0, 6), 'is_active' => false,
        ], $attributes));
    }

    private function makeLog(User $user, TaskTemplate $template, string $date, string $status): TaskLog
    {
        return $user->taskLogs()->create([
            'task_template_id' => $template->id,
            'date' => $date,
            'scheduled_at' => CarbonImmutable::parse($date.' '.$template->start_time, $user->timezone)->utc(),
            'status' => $status,
        ]);
    }
}
