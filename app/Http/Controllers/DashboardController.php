<?php

namespace App\Http\Controllers;

use App\Services\TaskPlannerService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TaskPlannerService $planner): View
    {
        $user = request()->user();
        $date = now($user->timezone)->toImmutable();
        if (! $user->taskTemplates()->withTrashed()->exists()) {
            $planner->seedDefaults($user);
        }
        $logs = $planner->ensureDay($user, $date);
        $summary = $planner->summary($user, $date);
        $streak = $planner->streak($user);
        $nextTask = $logs->first(fn ($log) => $log->status === 'pending');

        return view('dashboard', compact('logs', 'summary', 'streak', 'nextTask'));
    }
}
