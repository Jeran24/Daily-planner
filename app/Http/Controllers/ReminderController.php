<?php

namespace App\Http\Controllers;

use App\Models\TaskLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishlink\WebPush\ContentEncoding;

class ReminderController extends Controller
{
    public function due(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now('UTC')->toImmutable();
        $items = TaskLog::query()->with('template')
            ->where('user_id', $user->id)->where('status', 'pending')
            ->whereBetween('scheduled_at', [$now->subDay(), $now->addDay()])->get()
            ->filter(function (TaskLog $log) use ($now): bool {
                return $log->template && ! $log->template->trashed() && $log->template->is_active
                    && $log->scheduled_at->subMinutes($log->template->remind_minutes_before)->betweenIncluded($now->subMinute(), $now);
            })->map(fn (TaskLog $log) => [
                'id' => $log->id,
                'name' => $log->template->name,
                'start_time' => $log->scheduled_at->setTimezone($user->timezone)->format($user->time_format === '12h' ? 'g:i A' : 'H:i'),
            ])->values();

        return response()->json(['reminders' => $items]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'starts_with:https://', 'max:1024'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'device_label' => ['nullable', 'string', 'max:120'],
        ]);
        $user = $request->user();
        $subscription = $user->updatePushSubscription(
            $data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], ContentEncoding::aes128gcm,
        );
        $subscription->forceFill(['device_label' => $data['device_label'] ?? 'This browser'])->save();

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'starts_with:https://', 'max:1024']]);
        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }

    public function testReminder(): JsonResponse
    {
        return response()->json(['title' => 'Daily Planner test reminder', 'body' => 'Your reminders are ready on this device.']);
    }
}
