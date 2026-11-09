<?php

namespace Tests\Feature\Goals;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_are_private_editable_and_notes_are_encrypted(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->post(route('goals.projects.store'), [
            'name' => 'Planner refresh', 'description' => 'Improve the planner',
            'skills_needed' => "PHP\n CSS\nPHP", 'status' => 'active', 'priority' => 2,
            'notes' => 'private project note',
        ])->assertRedirect(route('goals.projects'));

        $project = $owner->projects()->firstOrFail();
        $this->assertSame(['PHP', 'CSS'], $project->skills_needed);
        $this->assertSame('private project note', $project->notes);
        $this->assertDatabaseMissing('projects', ['id' => $project->id, 'notes' => 'private project note']);
        $this->get(route('goals.projects'))->assertOk()->assertSee('Planner refresh')->assertSee('Private notes');
        $this->get(route('goals.projects.edit', $project))->assertOk()->assertSee('Private notes')->assertSee('Planner refresh');
        $this->actingAs($other)->get(route('goals.projects.edit', $project))->assertNotFound();
        $this->actingAs($owner)->delete(route('goals.projects.destroy', $project))->assertRedirect(route('goals.projects'));
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_reports_show_week_and_month_completion_and_owner_only_mood(): void
    {
        $owner = User::factory()->create(['timezone' => 'UTC', 'week_starts_on' => 1]);
        $other = User::factory()->create();
        $task = $owner->taskTemplates()->create([
            'name' => 'Study', 'category' => 'study', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => [0, 1, 2, 3, 4, 5, 6], 'is_active' => true,
        ]);
        $date = now('UTC')->startOfDay();
        $owner->taskLogs()->create([
            'task_template_id' => $task->id, 'date' => $date->toDateString(),
            'scheduled_at' => $date->copy()->setTime(9, 0), 'status' => 'done', 'minutes_spent' => 45,
        ]);
        $owner->journalEntries()->create(['date' => $date->toDateString(), 'mood' => 5, 'body' => 'good day']);
        $other->journalEntries()->create(['date' => $date->toDateString(), 'mood' => 1, 'body' => 'other owner']);

        $this->actingAs($owner)->get(route('goals.reports', ['period' => 'week']))->assertOk()
            ->assertSee('Study')->assertSee('45 min')->assertSee('5.0/5');
        $this->get(route('goals.reports', ['period' => 'month']))->assertOk();
    }
}
