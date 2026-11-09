<?php

namespace App\Http\Controllers;

use App\Services\TaskPlannerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __invoke(Request $request, TaskPlannerService $planner): View
    {
        $user = $request->user();
        $input = $request->validate([
            'month' => ['sometimes', 'date_format:Y-m'],
            'day' => ['sometimes', 'date_format:Y-m-d'],
        ]);
        $today = CarbonImmutable::now($user->timezone)->startOfDay();
        $month = isset($input['day'])
            ? CarbonImmutable::parse($input['day'], $user->timezone)->startOfMonth()
            : CarbonImmutable::createFromFormat('!Y-m', $input['month'] ?? $today->format('Y-m'), $user->timezone)->startOfMonth();
        $monthEnd = $month->endOfMonth();
        if ($today->greaterThanOrEqualTo($month) && $today->lessThanOrEqualTo($monthEnd)) {
            $planner->ensureDay($user, $today);
        }
        $monthLogs = $user->taskLogs()
            ->with('template')
            ->whereBetween('date', [$month->toDateString(), $monthEnd->toDateString()])
            ->orderBy('scheduled_at')
            ->get();
        $logsByDate = $monthLogs->groupBy(fn ($log) => CarbonImmutable::parse($log->date)->toDateString());

        $days = [];
        for ($cursor = $month; $cursor->month === $month->month; $cursor = $cursor->addDay()) {
            $dateKey = $cursor->toDateString();
            $dayLogs = $logsByDate->get($dateKey, collect());
            $summary = $planner->summaryFromLogs($dayLogs);
            $days[] = [
                'date' => $cursor,
                'summary' => $summary,
                'color' => $planner->colorFromSummary($summary),
                'isToday' => $cursor->equalTo($today),
                'isFuture' => $cursor->greaterThan($today),
            ];
        }

        $selectedDate = isset($input['day']) ? CarbonImmutable::parse($input['day'], $user->timezone)->startOfDay() : null;
        $selectedLogs = collect();
        $journal = null;
        $selectedSummary = null;
        if ($selectedDate) {
            if ($selectedDate->equalTo($today)) {
                $selectedLogs = $planner->ensureDay($user, $selectedDate);
            } elseif ($selectedDate->lessThan($today)) {
                $planner->finalizeDay($user, $selectedDate);
                $selectedLogs = $user->taskLogs()->with('template')
                    ->whereDate('date', $selectedDate->toDateString())
                    ->orderBy('scheduled_at')
                    ->get();
            } else {
                $selectedLogs = $user->taskLogs()->with('template')
                    ->whereDate('date', $selectedDate->toDateString())
                    ->orderBy('scheduled_at')
                    ->get();
            }

            $journal = $user->journalEntries()->where('date', $selectedDate->toDateString())->first();
            $selectedSummary = $planner->summaryFromLogs($selectedLogs);
        }

        $weekdays = collect(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
            ->concat(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'])
            ->slice($user->week_starts_on, 7)
            ->values();
        $leadingBlanks = ($month->dayOfWeek - $user->week_starts_on + 7) % 7;

        return view('calendar.index', compact('month', 'days', 'weekdays', 'leadingBlanks', 'selectedDate', 'selectedLogs', 'journal', 'selectedSummary'));
    }
}
