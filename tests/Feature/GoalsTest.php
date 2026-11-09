<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use App\Services\GoalProgressService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_checklist_minutes_task_count_and_manual_progress_are_calculated(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $service = app(GoalProgressService::class);

        $checklist = $this->goal($user, 'Checklist', 'checklist');
        $checklist->items()->createMany([
            ['title' => 'One', 'sort_order' => 0, 'done_at' => now('UTC')],
            ['title' => 'Two', 'sort_order' => 1, 'done_at' => null],
        ]);
        $this->assertSame(50.0, $service->progress($user, $checklist)[0]);

        $minutes = $this->goal($user, 'Minutes', 'minutes', ['target_value' => 200]);
        $this->taskWithLog($user, $minutes, 'No time typed', 'done', null, 60);
        $this->taskWithLog($user, $minutes, 'Timer entry', 'done', 30, 60);
        $this->assertSame(45.0, $service->progress($user, $minutes)[0]);

        $taskCount = $this->goal($user, 'Task count', 'task_count', ['target_value' => 4]);
        $this->taskWithLog($user, $taskCount, 'Counted task one', 'done', null, 60);
        $this->taskWithLog($user, $taskCount, 'Counted task two', 'done', null, 60);
        $this->taskWithLog($user, $taskCount, 'Pending task', 'pending', null, 60);
        $this->assertSame(50.0, $service->progress($user, $taskCount)[0]);

        $manual = $this->goal($user, 'Manual', 'manual', ['manual_percent' => 62]);
        $this->assertSame(62.0, $service->progress($user, $manual)[0]);
    }

    public function test_plan_marker_and_status_match_the_three_demo_goal_examples(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $service = app(GoalProgressService::class);

        $slightlyBehind = $this->goal($user, 'Slightly behind', 'manual', [
            'manual_percent' => 62, 'start_date' => '2026-10-25', 'end_date' => '2026-11-14',
        ]);
        $onTrack = $this->goal($user, 'On track', 'manual', [
            'manual_percent' => 45, 'start_date' => '2026-11-02', 'end_date' => '2026-11-17',
        ]);
        $atRisk = $this->goal($user, 'At risk', 'manual', [
            'manual_percent' => 18, 'start_date' => '2026-11-01', 'end_date' => '2026-11-21',
        ]);

        $a = $service->report($user, $slightlyBehind);
        $b = $service->report($user, $onTrack);
        $c = $service->report($user, $atRisk);

        $this->assertSame([62.0, 70.0, 'slightly_behind'], [$a['percent'], $a['plan'], $a['status']]);
        $this->assertSame([45.0, 40.0, 'on_track'], [$b['percent'], $b['plan'], $b['status']]);
        $this->assertSame([18.0, 35.0, 'at_risk'], [$c['percent'], $c['plan'], $c['status']]);
    }

    public function test_goal_feasibility_uses_recent_snapshots_and_estimates_a_finish_date(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $goal = $this->goal($user, 'Unrealistic pace', 'manual', [
            'manual_percent' => 20, 'start_date' => '2026-10-01', 'end_date' => '2026-11-18',
        ]);
        $goal->snapshots()->create(['date' => '2026-10-25', 'percent' => 10]);

        $feasibility = app(GoalProgressService::class)->report($user, $goal)['feasibility'];

        $this->assertTrue($feasibility['unrealistic']);
        $this->assertGreaterThan($feasibility['current_pace'] * 1.5, $feasibility['needed_pace']);
        $this->assertNotNull($feasibility['finish_date']);
        $this->assertSame(10, $feasibility['remaining_days']);
    }

    public function test_goal_items_and_templates_cannot_cross_user_boundaries(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $goal = $this->goal($other, 'Private goal', 'checklist');
        $item = $goal->items()->create(['title' => 'Private item', 'sort_order' => 0]);

        $this->actingAs($owner)->get(route('goals.edit', $goal->id))->assertNotFound();
        $this->actingAs($owner)->patch(route('goals.items.toggle', [$goal->id, $item->id]))->assertNotFound();
        $this->actingAs($owner)->get(route('goals'))->assertDontSee('Private goal');
    }

    public function test_checklist_can_be_created_updated_and_ticked(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('goals.store'), [
            'title' => 'Read more', 'start_date' => '2026-11-08', 'end_date' => '2026-12-08',
            'measure_type' => 'checklist', 'checklist_items' => "First chapter\nSecond chapter",
        ])->assertSessionHasNoErrors()->assertRedirect(route('goals'));
        $goal = $user->goals()->sole();
        $this->assertSame(2, $goal->items()->count());

        $item = $goal->items()->firstOrFail();
        $this->actingAs($user)->patch(route('goals.items.toggle', [$goal, $item]))->assertRedirect(route('goals.edit', $goal));
        $this->assertNotNull($item->fresh()->done_at);
        $this->assertSame(50.0, app(GoalProgressService::class)->progress($user, $goal)[0]);

        $this->actingAs($user)->put(route('goals.update', $goal), [
            'title' => 'Read many books', 'start_date' => '2026-11-08', 'end_date' => '2026-12-08',
            'measure_type' => 'checklist', 'checklist_items' => "First chapter\nSecond chapter\nThird chapter",
        ])->assertSessionHasNoErrors();
        $this->assertSame(3, $goal->items()->count());
        $this->assertSame(33.3, app(GoalProgressService::class)->progress($user, $goal)[0]);
    }

    public function test_goal_form_validates_measure_fields_and_links_only_to_owned_goals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $foreignGoal = $this->goal($other, 'Other user goal', 'manual', ['manual_percent' => 10]);

        $this->actingAs($owner)->post(route('goals.store'), [
            'title' => 'Invalid manual', 'start_date' => '2026-11-08', 'end_date' => '2026-11-07',
            'measure_type' => 'manual', 'manual_percent' => 120,
        ])->assertSessionHasErrors(['end_date', 'manual_percent']);

        $this->actingAs($owner)->post(route('tasks.store'), [
            'name' => 'Foreign linked task', 'category' => 'other', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => [0], 'goal_id' => $foreignGoal->id,
        ])->assertSessionHasErrors('goal_id');
        $this->assertSame(0, $owner->taskTemplates()->count());
    }

    public function test_linked_task_progress_uses_owner_logs_and_routine_is_cleared_from_future_when_goal_is_deleted(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $goal = $this->goal($user, 'Linked study', 'task_count', ['target_value' => 2]);
        $template = $user->taskTemplates()->create([
            'name' => 'Study', 'category' => 'study', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => range(0, 6), 'is_active' => false, 'goal_id' => $goal->id,
        ]);
        $user->taskLogs()->create([
            'task_template_id' => $template->id, 'date' => '2026-11-07', 'scheduled_at' => '2026-11-07 07:00:00', 'status' => 'done',
        ]);
        $this->assertSame(50.0, app(GoalProgressService::class)->progress($user, $goal)[0]);

        $pastLog = $user->taskLogs()->firstOrFail();
        $this->actingAs($user)->delete(route('goals.destroy', $goal))->assertRedirect(route('goals'));
        $this->assertSoftDeleted('goals', ['id' => $goal->id]);
        $this->assertNotNull($pastLog->fresh());
        $this->assertNull($template->fresh()->goal_id);
    }

    private function goal(User $user, string $title, string $measure, array $attributes = []): Goal
    {
        return $user->goals()->create(array_merge([
            'title' => $title,
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-01',
            'measure_type' => $measure,
            'status' => 'active',
        ], $attributes));
    }

    private function taskWithLog(User $user, Goal $goal, string $name, string $status, ?int $minutes, int $duration): void
    {
        $template = $user->taskTemplates()->create([
            'name' => $name, 'category' => 'study', 'start_time' => '09:00',
            'duration_minutes' => $duration, 'weekdays' => range(0, 6), 'is_active' => false, 'goal_id' => $goal->id,
        ]);
        $user->taskLogs()->create([
            'task_template_id' => $template->id,
            'date' => '2026-11-07',
            'scheduled_at' => '2026-11-07 07:00:00',
            'status' => $status,
            'minutes_spent' => $minutes,
        ]);
    }
}
