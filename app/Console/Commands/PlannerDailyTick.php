<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GoalProgressService;
use App\Services\TaskPlannerService;
use Illuminate\Console\Command;

class PlannerDailyTick extends Command
{
    protected $signature = 'planner:daily-tick';

    protected $description = "Generate today's tasks and close each owner's previous day";

    public function handle(TaskPlannerService $planner, GoalProgressService $goals): int
    {
        User::query()->chunkById(100, function ($users) use ($planner): void {
            foreach ($users as $user) {
                if (! $user->taskTemplates()->withTrashed()->exists()) {
                    $planner->seedDefaults($user);
                }

                $today = now($user->timezone)->toImmutable();
                $planner->ensureDay($user, $today);
                $yesterday = $today->subDay();
                $planner->finalizeDay($user, $yesterday);
                foreach ($user->goals()->where('status', 'active')->get() as $goal) {
                    if (! $goal->snapshots()->whereDate('date', $yesterday->toDateString())->exists()) {
                        $goals->recordSnapshot($user, $goal, $yesterday);
                    }
                }
            }
        });

        $this->info("Generated today's task logs and finalized pending tasks for each owner timezone.");

        return self::SUCCESS;
    }
}
