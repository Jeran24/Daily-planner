<?php

namespace App\Console\Commands;

use App\Models\TaskLog;
use App\Notifications\TaskReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendPlannerReminders extends Command
{
    protected $signature = 'planner:send-reminders';

    protected $description = 'Send one Web Push reminder for each due pending task';

    public function handle(): int
    {
        $now = CarbonImmutable::now('UTC');
        $windowStart = $now->subMinute();
        $logs = TaskLog::query()->with(['user.pushSubscriptions', 'template'])
            ->where('status', 'pending')->whereBetween('scheduled_at', [$windowStart, $now->addDay()])
            ->get();
        $sent = 0;

        foreach ($logs as $log) {
            $template = $log->template;
            $user = $log->user;
            if (! $template || $template->trashed() || ! $template->is_active || $user->pushSubscriptions->isEmpty()) {
                continue;
            }

            $remindAt = $log->scheduled_at->subMinutes($template->remind_minutes_before);
            if ($remindAt->lt($windowStart) || $remindAt->gt($now)) {
                continue;
            }

            $eventId = DB::table('reminder_events')->insertOrIgnore([
                'task_log_id' => $log->id,
                'kind' => 'push',
                'sent_at' => $now->toDateTimeString(),
                'created_at' => $now->toDateTimeString(),
                'updated_at' => $now->toDateTimeString(),
            ]);
            if (! $eventId) {
                continue;
            }

            try {
                $user->notify(new TaskReminderNotification(
                    $template->name,
                    $log->scheduled_at->setTimezone($user->timezone)->format($user->time_format === '12h' ? 'g:i A' : 'H:i'),
                ));
                $sent++;
            } catch (Throwable $exception) {
                DB::table('reminder_events')->where('task_log_id', $log->id)->where('kind', 'push')->delete();
                report($exception);
            }
        }

        $this->info("Sent {$sent} task reminder(s).");

        return self::SUCCESS;
    }
}
