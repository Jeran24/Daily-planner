<?php

namespace Tests\Feature;

use App\Models\TaskLog;
use App\Models\User;
use App\Services\GoalProgressService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_month_calendar_shows_all_completion_colors_and_marks_days_with_tasks(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $this->log($user, '2026-11-01', 'done', 'Green');
        $this->log($user, '2026-11-02', 'done', 'Yellow done');
        $this->log($user, '2026-11-02', 'pending', 'Yellow pending');
        $this->log($user, '2026-11-03', 'done', 'Orange done');
        $this->log($user, '2026-11-03', 'pending', 'Orange pending one');
        $this->log($user, '2026-11-03', 'pending', 'Orange pending two');
        $this->log($user, '2026-11-04', 'pending', 'Red');

        $response = $this->actingAs($user)->get(route('calendar', ['month' => '2026-11']));

        $response->assertOk()
            ->assertSee('status-green')
            ->assertSee('status-yellow')
            ->assertSee('status-orange')
            ->assertSee('status-red')
            ->assertSee('2026-11-01')
            ->assertSee('Sun');
    }

    public function test_journal_is_encrypted_saved_once_per_day_and_scoped_to_its_owner(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $date = '2026-11-07';
        $secret = 'a private journal line about my day';

        $this->actingAs($owner)->post(route('journal.store'), [
            'date' => $date, 'mood' => 4, 'body' => $secret,
        ])->assertSessionHasNoErrors()->assertRedirect(route('journal', ['date' => $date]));
        $this->actingAs($owner)->post(route('journal.store'), [
            'date' => $date, 'mood' => 5, 'body' => 'Updated entry',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $owner->journalEntries()->count());
        $entry = $owner->journalEntries()->firstOrFail();
        $this->assertSame(5, $entry->mood);
        $stored = DB::table('journal_entries')->where('id', $entry->id)->value('body');
        $this->assertStringNotContainsString('Updated entry', $stored);
        $this->assertSame('Updated entry', $entry->body);

        $this->actingAs($other)->get(route('journal', ['date' => $date]))->assertOk()->assertDontSee('Updated entry');
        $this->assertSame(0, $other->journalEntries()->count());
    }

    public function test_past_day_detail_finalizes_pending_logs_and_shows_that_days_journal(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $pending = $this->log($user, '2026-11-04', 'pending', 'Past task');
        $user->journalEntries()->create(['date' => '2026-11-04', 'mood' => 3, 'body' => 'A note from Wednesday']);

        $this->actingAs($user)->get(route('calendar', ['day' => '2026-11-04']))
            ->assertOk()
            ->assertSee('Past task')
            ->assertSee('A note from Wednesday')
            ->assertSee('Missed');

        $this->assertSame('missed', $pending->fresh()->status);
    }

    public function test_past_calendar_days_without_saved_logs_are_not_backfilled_from_today_routines(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();
        $user->taskTemplates()->create([
            'name' => 'Current routine', 'category' => 'other', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => range(0, 6), 'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('calendar', ['day' => '2026-11-04']))
            ->assertOk()->assertSee('No task history for this day');

        $this->assertSame(0, $user->taskLogs()->whereDate('date', '2026-11-04')->count());
    }

    public function test_journal_rejects_future_dates_and_invalid_moods(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('journal.store'), [
            'date' => '2026-11-09', 'mood' => 9, 'body' => '',
        ])->assertSessionHasErrors(['date', 'mood', 'body']);
        $this->assertSame(0, $user->journalEntries()->count());
    }

    public function test_daily_tick_generates_today_and_marks_the_finished_day_missed(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 00:01:00 Africa/Kigali');
        $user = User::factory()->create();
        $pending = $this->log($user, '2026-11-07', 'pending', 'Yesterday task');
        $pending->template->update(['is_active' => true]);

        $this->artisan('planner:daily-tick')->assertSuccessful();

        $this->assertSame('missed', $pending->fresh()->status);
        $this->assertGreaterThan(0, $user->taskLogs()->whereDate('date', '2026-11-08')->count());
    }

    public function test_demo_seed_creates_local_history_and_refuses_production_or_existing_owners(): void
    {
        CarbonImmutable::setTestNow('2026-11-08 10:00:00 Africa/Kigali');
        $this->artisan('demo:seed')->assertSuccessful();
        $demo = User::query()->sole();
        $this->assertSame('demo.owner@example.test', $demo->email);
        $this->assertSame(6, $demo->taskTemplates()->count());
        $this->assertSame(6, $demo->journalEntries()->count());
        $this->assertGreaterThanOrEqual(30, $demo->taskLogs()->distinct('date')->count('date'));
        $sampleReports = $demo->goals()->orderBy('title')->get()->map(fn ($goal) => app(GoalProgressService::class)->report($demo, $goal));
        $this->assertSame([18.0, 35.0, 'at_risk'], [$sampleReports[0]['percent'], $sampleReports[0]['plan'], $sampleReports[0]['status']]);
        $this->assertSame([62.0, 70.0, 'slightly_behind'], [$sampleReports[1]['percent'], $sampleReports[1]['plan'], $sampleReports[1]['status']]);
        $this->assertSame([45.0, 40.0, 'on_track'], [$sampleReports[2]['percent'], $sampleReports[2]['plan'], $sampleReports[2]['status']]);
        $this->artisan('demo:seed')->assertFailed();
        $this->assertSame(1, User::query()->count());

    }

    public function test_demo_seed_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('demo:seed')->assertFailed();
        $this->assertSame(0, User::query()->count());
    }

    private function log(User $user, string $date, string $status, string $name): TaskLog
    {
        $template = $user->taskTemplates()->create([
            'name' => $name, 'category' => 'other', 'start_time' => '09:00',
            'duration_minutes' => 60, 'weekdays' => range(0, 6), 'is_active' => false,
        ]);

        return $user->taskLogs()->create([
            'task_template_id' => $template->id,
            'date' => $date,
            'scheduled_at' => CarbonImmutable::parse($date.' 09:00', $user->timezone)->utc(),
            'status' => $status,
        ]);
    }
}
