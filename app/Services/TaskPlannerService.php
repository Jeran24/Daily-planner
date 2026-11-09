<?php

namespace App\Services;

use App\Models\TaskLog;
use App\Models\TaskTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskPlannerService
{
    /** @return array<int, array{name:string,category:string,time:string,weekdays:array<int,int>}> */
    public function defaults(): array
    {
        $everyDay = range(0, 6);
        $sunToFri = range(0, 5);

        return [
            ['name' => 'Read Bible', 'category' => 'faith', 'time' => '06:00', 'weekdays' => $everyDay],
            ['name' => 'Health care', 'category' => 'health', 'time' => '07:00', 'weekdays' => $everyDay],
            ['name' => 'Learn Python', 'category' => 'coding', 'time' => '17:00', 'weekdays' => $sunToFri],
            ['name' => 'Revise notebook', 'category' => 'study', 'time' => '18:00', 'weekdays' => $sunToFri],
            ['name' => 'Learn JavaScript', 'category' => 'coding', 'time' => '19:00', 'weekdays' => $sunToFri],
            ['name' => 'Learn Laravel (PHP fundamentals to the framework)', 'category' => 'coding', 'time' => '20:00', 'weekdays' => $sunToFri],
        ];
    }

    public function seedDefaults(User $user): void
    {
        DB::transaction(function () use ($user): void {
            foreach ($this->defaults() as $sort => $default) {
                $template = TaskTemplate::withTrashed()
                    ->where('user_id', $user->id)
                    ->where('name', $default['name'])
                    ->where('is_default', true)
                    ->first();

                if ($template) {
                    if ($template->trashed()) {
                        $template->restore();
                        $template->update(['is_active' => true]);
                    }

                    continue;
                }

                $user->taskTemplates()->create([
                    'name' => $default['name'],
                    'category' => $default['category'],
                    'start_time' => $default['time'],
                    'weekdays' => $default['weekdays'],
                    'duration_minutes' => 60,
                    'is_default' => true,
                    'is_active' => true,
                    'remind_minutes_before' => 5,
                    'alarm_enabled' => false,
                    'sort_order' => $sort,
                ]);
            }
        });
    }

    public function ensureDay(User $user, CarbonImmutable|string $date): Collection
    {
        $localDate = $this->date($user, $date);

        if ($localDate->lessThan($this->today($user))) {
            $this->finalizeDay($user, $localDate);

            return $user->taskLogs()
                ->with('template')
                ->whereDate('date', $localDate->toDateString())
                ->orderBy('scheduled_at')
                ->get();
        }

        $templates = $user->taskTemplates()
            ->where('is_active', true)
            ->whereJsonContains('weekdays', $localDate->dayOfWeek)
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->get();

        foreach ($templates as $template) {
            $scheduledLocal = CarbonImmutable::parse($localDate->format('Y-m-d').' '.$template->start_time, $user->timezone);
            $user->taskLogs()->firstOrCreate(
                ['task_template_id' => $template->id, 'date' => $localDate->toDateString()],
                ['scheduled_at' => $scheduledLocal->utc(), 'status' => 'pending'],
            );
        }

        return $user->taskLogs()
            ->with('template')
            ->whereDate('date', $localDate->toDateString())
            ->orderBy('scheduled_at')
            ->get();
    }

    public function updateStatus(User $user, TaskLog $log, string $status): TaskLog
    {
        abort_unless($log->user_id === $user->id, 404);
        abort_unless(in_array($status, ['pending', 'done', 'minimum', 'skipped'], true), 422);

        $log->forceFill([
            'status' => $status,
            'completed_at' => in_array($status, ['done', 'minimum'], true) ? now('UTC') : null,
        ])->save();

        return $log->refresh();
    }

    public function finalizeDay(User $user, CarbonImmutable|string $date): void
    {
        $localDate = $this->date($user, $date);
        if (! $localDate->startOfDay()->lessThan($this->today($user))) {
            return;
        }

        $user->taskLogs()
            ->whereDate('date', $localDate->toDateString())
            ->where('status', 'pending')
            ->update(['status' => 'missed', 'updated_at' => now('UTC')]);
    }

    /** @return array{total:int,done:int,minimum:int,skipped:int,remaining:int,percent:float|null} */
    public function summary(User $user, CarbonImmutable|string $date): array
    {
        $localDate = $this->date($user, $date);
        $logs = $user->taskLogs()->whereDate('date', $localDate->toDateString())->get();

        return $this->summaryFromLogs($logs);
    }

    /** @param iterable<TaskLog> $logs
     * @return array{total:int,done:int,minimum:int,skipped:int,remaining:int,percent:float|null}
     */
    public function summaryFromLogs(iterable $logs): array
    {
        $logs = collect($logs);
        $skipped = $logs->where('status', 'skipped')->count();
        $denominator = $logs->count() - $skipped;
        $done = $logs->where('status', 'done')->count();
        $minimum = $logs->where('status', 'minimum')->count();

        return [
            'total' => $logs->count(),
            'done' => $done,
            'minimum' => $minimum,
            'skipped' => $skipped,
            'remaining' => $denominator - $done - $minimum,
            'percent' => $denominator === 0 ? null : round(($done + 0.5 * $minimum) / $denominator * 100, 1),
        ];
    }

    public function color(User $user, CarbonImmutable|string $date): ?string
    {
        return $this->colorFromSummary($this->summary($user, $date));
    }

    /** @param array{percent:float|null} $summary */
    public function colorFromSummary(array $summary): ?string
    {
        $percent = $summary['percent'];
        if ($percent === null) {
            return null;
        }

        return match (true) {
            $percent >= 80 => 'green',
            $percent >= 50 => 'yellow',
            $percent > 0 => 'orange',
            default => 'red',
        };
    }

    public function streak(User $user): int
    {
        $today = $this->today($user);
        $rows = $user->taskLogs()
            ->whereDate('date', '>=', $today->subDays(365)->toDateString())
            ->get(['date', 'status']);

        $byDay = $rows->groupBy(fn (TaskLog $log) => CarbonImmutable::parse($log->date)->toDateString());
        $threshold = (float) $user->settings()->where('key', 'streak_threshold')->value('value') ?: 80.0;
        $cursor = $today;

        $todaySummary = $this->summary($user, $today);
        if ($todaySummary['percent'] === null || $todaySummary['percent'] < $threshold) {
            $cursor = $today->subDay();
        }

        $streak = 0;
        for ($daysChecked = 0; $daysChecked < 366; $daysChecked++, $cursor = $cursor->subDay()) {
            $dateKey = $cursor->toDateString();
            $logs = $byDay->get($dateKey);
            if (! $logs || $logs->isEmpty()) {
                continue;
            }

            $skipped = $logs->where('status', 'skipped')->count();
            $denominator = $logs->count() - $skipped;
            if ($denominator === 0) {
                continue;
            }

            $percent = ($logs->where('status', 'done')->count() + 0.5 * $logs->where('status', 'minimum')->count()) / $denominator * 100;
            if ($percent < $threshold) {
                break;
            }

            $streak++;
        }

        return $streak;
    }

    public function reminderAt(User $user, TaskTemplate $template, CarbonImmutable|string $date): CarbonImmutable
    {
        abort_unless($template->user_id === $user->id, 404);
        $localDate = $this->date($user, $date);

        return CarbonImmutable::parse($localDate->format('Y-m-d').' '.$template->start_time, $user->timezone)
            ->subMinutes($template->remind_minutes_before)
            ->utc();
    }

    private function date(User $user, CarbonImmutable|string $date): CarbonImmutable
    {
        return ($date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date, $user->timezone))
            ->setTimezone($user->timezone)
            ->startOfDay();
    }

    private function today(User $user): CarbonImmutable
    {
        return CarbonImmutable::now($user->timezone)->startOfDay();
    }
}
