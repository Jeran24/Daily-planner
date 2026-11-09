<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoalProgressService;
use App\Services\TaskPlannerService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDemoPlanner extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Create a local-only demo planner and 30 days of sample history';

    private const EMAIL = 'demo.owner@example.test';

    public function handle(TaskPlannerService $planner, GoalProgressService $goals): int
    {
        if (app()->environment('production')) {
            $this->error('The demo seeder is disabled in production.');

            return self::FAILURE;
        }

        if (User::query()->where('email', self::EMAIL)->exists()) {
            $this->error('The demo account already exists in this database.');

            return self::FAILURE;
        }

        $password = config('demo.password') ?: Str::password(24);

        $user = User::create([
            'name' => 'Demo Owner',
            'email' => self::EMAIL,
            'password' => $password,
            'timezone' => 'Africa/Kigali',
            'week_starts_on' => 0,
            'theme' => 'system',
            'time_format' => '24h',
        ]);
        $planner->seedDefaults($user);

        $today = CarbonImmutable::now($user->timezone)->startOfDay();
        for ($offset = 29; $offset >= 0; $offset--) {
            $date = $today->subDays($offset);
            $templates = $user->taskTemplates()
                ->where('is_active', true)
                ->whereJsonContains('weekdays', $date->dayOfWeek)
                ->orderBy('sort_order')
                ->get();
            $pattern = $offset % 4;

            foreach ($templates as $index => $template) {
                $status = match ($pattern) {
                    0 => 'done',
                    1 => $index < (int) ceil($templates->count() / 2) ? 'done' : 'pending',
                    2 => $index === 0 ? 'done' : 'pending',
                    default => 'pending',
                };
                $localTime = CarbonImmutable::parse($date->toDateString().' '.$template->start_time, $user->timezone);

                $user->taskLogs()->firstOrCreate(
                    ['task_template_id' => $template->id, 'date' => $date->toDateString()],
                    [
                        'scheduled_at' => $localTime->utc(),
                        'status' => $status,
                        'completed_at' => $status === 'done' ? $localTime->utc()->addMinutes($template->duration_minutes) : null,
                    ],
                );
            }
        }

        foreach ([1, 5, 10, 16, 23, 28] as $offset) {
            $date = $today->subDays($offset)->toDateString();
            $user->journalEntries()->firstOrCreate(
                ['date' => $date],
                ['mood' => [1 => 4, 5 => 3, 10 => 5, 16 => 2, 23 => 4, 28 => 3][$offset],
                    'body' => 'Demo journal entry for '.$date.'. Your own entries are stored encrypted.'],
            );
        }

        $sampleGoals = [
            ['title' => 'Complete my study plan', 'percent' => 62, 'start' => 14, 'end' => 6, 'baseline_days' => 14, 'baseline_percent' => 40],
            ['title' => 'Ship a small web app', 'percent' => 45, 'start' => 6, 'end' => 9, 'baseline_days' => 6, 'baseline_percent' => 40],
            ['title' => 'Build a steady reading habit', 'percent' => 18, 'start' => 7, 'end' => 13, 'baseline_days' => 7, 'baseline_percent' => 12],
        ];
        foreach ($sampleGoals as $sample) {
            $goal = $user->goals()->create([
                'title' => $sample['title'],
                'start_date' => $today->subDays($sample['start'])->toDateString(),
                'end_date' => $today->addDays($sample['end'])->toDateString(),
                'measure_type' => 'manual',
                'manual_percent' => $sample['percent'],
                'status' => 'active',
            ]);
            $goal->snapshots()->create([
                'date' => $today->subDays($sample['baseline_days'])->toDateString(),
                'percent' => $sample['baseline_percent'],
            ]);
            $goals->recordSnapshot($user, $goal, $today);
        }

        $user->projects()->createMany([
            [
                'name' => 'Daily Planner Foundations',
                'description' => 'Shape the core routine, task, and journal experience.',
                'skills_needed' => ['Laravel', 'Product design', 'Testing'],
                'status' => 'active',
                'priority' => 1,
                'notes' => 'Keep the first release small and dependable.',
            ],
            [
                'name' => 'Study Tracker',
                'description' => 'Explore a simple way to track learning milestones.',
                'skills_needed' => ['JavaScript', 'Data modeling'],
                'status' => 'idea',
                'priority' => 3,
                'notes' => 'Start with a paper sketch before adding tasks.',
            ],
        ]);

        $this->info('Demo planner created with 30 days of task history.');
        $this->line('Email: '.self::EMAIL);
        $this->line('Password: '.$password);

        return self::SUCCESS;
    }
}
