<?php

namespace App\Http\Controllers;

use App\Services\PlannerExportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function reminders(Request $request): View
    {
        $user = $request->user();

        return view('settings.reminders', [
            'minutes' => (int) ($user->settings()->where('key', 'default_remind_minutes_before')->value('value') ?? 5),
            'alarmEnabled' => filter_var($user->settings()->where('key', 'default_alarm_enabled')->value('value') ?? false, FILTER_VALIDATE_BOOL),
            'pushEnabled' => $user->pushSubscriptions()->exists(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ]);
    }

    public function updateReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'remind_minutes_before' => ['required', 'integer', 'between:0,1440'],
            'alarm_enabled' => ['sometimes', 'boolean'],
        ]);
        $this->storeSetting($request, 'default_remind_minutes_before', (string) $data['remind_minutes_before']);
        $this->storeSetting($request, 'default_alarm_enabled', isset($data['alarm_enabled']) ? '1' : '0');

        return back()->with('status', 'Reminder defaults saved.');
    }

    public function goals(Request $request): View
    {
        $user = $request->user();

        return view('settings.goals', [
            'weekStartsOn' => $user->week_starts_on,
            'streakThreshold' => (float) ($user->settings()->where('key', 'streak_threshold')->value('value') ?? 80),
        ]);
    }

    public function updateGoals(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week_starts_on' => ['required', 'integer', 'between:0,6'],
            'streak_threshold' => ['required', 'numeric', 'between:1,100'],
        ]);
        $request->user()->update(['week_starts_on' => $data['week_starts_on']]);
        $this->storeSetting($request, 'streak_threshold', (string) $data['streak_threshold']);

        return back()->with('status', 'Goal and calendar preferences saved.');
    }

    public function privacy(Request $request): View
    {
        $timeout = (int) ($request->user()->settings()->where('key', 'lock_timeout_minutes')->value('value') ?? 10);
        $currentId = $request->session()->getId();
        $devices = DB::table('sessions')->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn ($session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => Str::limit($session->user_agent ?? 'Unknown device', 160),
                'last_activity' => CarbonImmutable::createFromTimestampUTC((int) $session->last_activity)->setTimezone($request->user()->timezone)->diffForHumans(),
                'current' => hash_equals((string) $currentId, (string) $session->id),
            ]);

        return view('settings.privacy', compact('timeout', 'devices'));
    }

    public function updatePrivacy(Request $request): RedirectResponse
    {
        $data = $request->validate(['lock_timeout_minutes' => ['required', 'integer', 'between:1,120']]);
        $this->storeSetting($request, 'lock_timeout_minutes', (string) $data['lock_timeout_minutes']);

        return back()->with('status', 'Privacy settings saved.');
    }

    public function revokeDevice(Request $request, string $session): RedirectResponse
    {
        abort_if(hash_equals($request->session()->getId(), $session), 403, 'Use Log out to end the current session.');
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $session)->delete();

        return back()->with('status', 'That device session was logged out.');
    }

    public function data(): View
    {
        return view('settings.data');
    }

    public function exportJson(Request $request, PlannerExportService $export)
    {
        $filename = 'daily-planner-export-'.$request->user()->id.'-'.now('UTC')->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($request, $export): void {
            echo json_encode($export->data($request->user()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function exportCsv(Request $request, PlannerExportService $export)
    {
        $filename = 'daily-planner-export-'.$request->user()->id.'-'.now('UTC')->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request, $export): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, ['section', 'data']);
            foreach ($export->data($request->user()) as $section => $rows) {
                $records = is_array($rows) && array_is_list($rows) ? $rows : [$rows];
                foreach ($records as $row) {
                    fputcsv($output, [$section, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
                }
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function deleteAccount(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Your account and planner data were deleted.');
    }

    public function lock(): View
    {
        return view('auth.lock');
    }

    public function unlock(Request $request): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        $request->session()->forget('planner_locked');
        $request->session()->put('planner_last_activity', now('UTC')->timestamp);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    private function storeSetting(Request $request, string $key, string $value): void
    {
        $request->user()->settings()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
