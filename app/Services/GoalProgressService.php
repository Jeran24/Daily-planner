<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\GoalProgressSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;

class GoalProgressService
{
    /** @return array{percent:float,plan:float,gap:float,status:string,done:int,total:int,feasibility:array{unrealistic:bool,needed_pace:float,current_pace:float,finish_date:string|null,remaining_days:int}} */
    public function report(User $user, Goal $goal, ?CarbonImmutable $date = null): array
    {
        abort_unless($goal->user_id === $user->id, 404);

        $today = ($date ?? CarbonImmutable::now($user->timezone))->setTimezone($user->timezone)->startOfDay();
        [$percent, $done, $total] = $this->progress($user, $goal);
        $start = CarbonImmutable::parse($goal->start_date->toDateString(), $user->timezone)->startOfDay();
        $end = CarbonImmutable::parse($goal->end_date->toDateString(), $user->timezone)->startOfDay();
        $totalDays = max(1, (int) $start->diffInDays($end));
        $elapsedDays = $today->lessThanOrEqualTo($start) ? 0 : min($totalDays, (int) $start->diffInDays($today));
        $plan = $today->greaterThanOrEqualTo($end) ? 100.0 : round($elapsedDays / $totalDays * 100, 1);
        $gap = round($plan - $percent, 1);
        $status = $percent >= 100 ? 'completed' : match (true) {
            $gap <= 0 => 'on_track',
            $gap <= 10 => 'slightly_behind',
            default => 'at_risk',
        };

        return [
            'percent' => $percent,
            'plan' => $plan,
            'gap' => $gap,
            'status' => $status,
            'done' => $done,
            'total' => $total,
            'feasibility' => $this->feasibility($user, $goal, $percent, $today, $end),
        ];
    }

    /** @return array{0:float,1:int,2:int} */
    public function progress(User $user, Goal $goal): array
    {
        abort_unless($goal->user_id === $user->id, 404);

        return match ($goal->measure_type) {
            'checklist' => $this->checklistProgress($goal),
            'minutes' => $this->taskProgress($user, $goal, 'minutes'),
            'task_count' => $this->taskProgress($user, $goal, 'task_count'),
            'manual' => [min(100, max(0, (float) $goal->manual_percent)), (int) round((float) $goal->manual_percent), 100],
            default => [0.0, 0, 0],
        };
    }

    public function recordSnapshot(User $user, Goal $goal, CarbonImmutable|string $date): GoalProgressSnapshot
    {
        abort_unless($goal->user_id === $user->id, 404);
        $localDate = ($date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date, $user->timezone))
            ->setTimezone($user->timezone)->toDateString();
        $percent = $this->progress($user, $goal)[0];

        return $goal->snapshots()->updateOrCreate(['date' => $localDate], ['percent' => $percent]);
    }

    /** @return array{0:float,1:int,2:int} */
    private function checklistProgress(Goal $goal): array
    {
        $items = $goal->items()->get();
        $total = $items->count();
        $done = $items->whereNotNull('done_at')->count();

        return [$total === 0 ? 0.0 : round($done / $total * 100, 1), $done, $total];
    }

    /** @return array{0:float,1:int,2:int} */
    private function taskProgress(User $user, Goal $goal, string $measure): array
    {
        $logs = $user->taskLogs()
            ->with('template')
            ->where('status', 'done')
            ->whereDate('date', '<=', CarbonImmutable::now($user->timezone)->toDateString())
            ->whereHas('template', fn ($query) => $query->where('user_id', $user->id)->where('goal_id', $goal->id))
            ->get();
        $target = max(1, (int) $goal->target_value);

        if ($measure === 'task_count') {
            $done = $logs->count();

            return [min(100.0, round($done / $target * 100, 1)), $done, $target];
        }

        $minutes = $logs->sum(fn ($log) => $log->minutes_spent ?? $log->template?->duration_minutes ?? 0);

        return [min(100.0, round($minutes / $target * 100, 1)), $minutes, $target];
    }

    /** @return array{unrealistic:bool,needed_pace:float,current_pace:float,finish_date:string|null,remaining_days:int} */
    private function feasibility(User $user, Goal $goal, float $actual, CarbonImmutable $today, CarbonImmutable $end): array
    {
        $remainingDays = max(0, (int) $today->diffInDays($end, false));
        $remainingPercent = max(0, 100 - $actual);
        $neededPace = $remainingDays > 0 ? round($remainingPercent / $remainingDays, 2) : 0.0;
        $cutoff = $today->subDays(14);
        $baseline = $goal->snapshots()->whereDate('date', '>=', $cutoff->subDay()->toDateString())
            ->whereDate('date', '<=', $cutoff->toDateString())
            ->orderByDesc('date')->first();
        if (! $baseline) {
            $baseline = $goal->snapshots()->whereDate('date', '>', $cutoff->toDateString())
                ->whereDate('date', '<', $today->toDateString())->orderBy('date')->first();
        }

        $currentPace = 0.0;
        if ($baseline) {
            $baselineDate = CarbonImmutable::parse($baseline->date, $user->timezone)->startOfDay();
            $days = max(1, (int) $baselineDate->diffInDays($today));
            $currentPace = max(0.0, round(($actual - (float) $baseline->percent) / $days, 2));
        }

        $unrealistic = $remainingDays > 0 && $neededPace > 1.5 * $currentPace;
        $finishDate = $currentPace > 0 && $remainingPercent > 0
            ? $today->addDays((int) ceil($remainingPercent / $currentPace))->toDateString()
            : null;

        return [
            'unrealistic' => $unrealistic,
            'needed_pace' => $neededPace,
            'current_pace' => $currentPace,
            'finish_date' => $finishDate,
            'remaining_days' => $remainingDays,
        ];
    }
}
