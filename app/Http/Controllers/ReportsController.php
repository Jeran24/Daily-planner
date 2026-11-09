<?php

namespace App\Http\Controllers;

use App\Services\TaskPlannerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __invoke(Request $request, TaskPlannerService $planner): View
    {
        $data = $request->validate(['period' => ['sometimes', 'in:week,month']]);
        $period = $data['period'] ?? 'week';
        $user = $request->user();
        $today = CarbonImmutable::now($user->timezone)->startOfDay();
        if ($period === 'week') {
            $back = ($today->dayOfWeek - $user->week_starts_on + 7) % 7;
            $start = $today->subDays($back);
            $end = $start->addDays(6);
        } else {
            $start = $today->startOfMonth();
            $end = $today->endOfMonth();
        }

        $logs = $user->taskLogs()->with('template')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('scheduled_at')->get();
        $summary = $planner->summaryFromLogs($logs);
        $days = $logs->groupBy(fn ($log) => CarbonImmutable::parse($log->date)->toDateString())
            ->map(function ($dayLogs, $date) use ($planner): array {
                return ['date' => $date, ...$planner->summaryFromLogs($dayLogs)];
            })->filter(fn ($day) => $day['percent'] !== null)->values();
        $bestDay = $days->sortByDesc('percent')->first();
        $worstDay = $days->sortBy('percent')->first();
        $minutesByCategory = $logs->where('status', 'done')->groupBy(fn ($log) => $log->template?->category ?? 'other')
            ->map(fn ($categoryLogs) => $categoryLogs->sum(fn ($log) => $log->minutes_spent ?? $log->template?->duration_minutes ?? 0));
        $averageMood = $user->journalEntries()->whereBetween('date', [$start->toDateString(), $end->toDateString()])->avg('mood');
        $streak = $planner->streak($user);

        return view('goals.reports.index', compact('period', 'start', 'end', 'summary', 'bestDay', 'worstDay', 'minutesByCategory', 'averageMood', 'streak'));
    }
}
