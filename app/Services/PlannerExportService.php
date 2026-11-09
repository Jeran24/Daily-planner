<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlannerExportService
{
    /** @return array<string, mixed> */
    public function data(User $user): array
    {
        $data = [
            'exported_at' => now('UTC')->toIso8601String(),
            'user' => $user->only(['id', 'name', 'email', 'timezone', 'week_starts_on', 'theme', 'time_format', 'created_at']),
            'settings' => $user->settings()->get()->toArray(),
            'task_templates' => $user->taskTemplates()->withTrashed()->get()->toArray(),
            'task_logs' => $user->taskLogs()->get()->toArray(),
            'journal_entries' => $user->journalEntries()->get()->toArray(),
            'goals' => $user->goals()->withTrashed()->with(['items', 'snapshots'])->get()->toArray(),
        ];

        if (Schema::hasTable('projects')) {
            $data['projects'] = $user->projects()->withTrashed()->get()->toArray();
        }

        if (Schema::hasTable('push_subscriptions')) {
            $data['push_subscriptions'] = DB::table('push_subscriptions')->where('user_id', $user->id)->get()->toArray();
        }

        if (Schema::hasTable('reminder_events')) {
            $logIds = $user->taskLogs()->pluck('id');
            $data['reminder_events'] = DB::table('reminder_events')->whereIn('task_log_id', $logIds)->get()->toArray();
        }

        return $data;
    }
}
