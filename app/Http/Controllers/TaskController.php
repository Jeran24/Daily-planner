<?php

namespace App\Http\Controllers;

use App\Services\TaskPlannerService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $tasks = $request->user()->taskTemplates()->with('goal')->orderBy('sort_order')->orderBy('start_time')->get();

        return view('tasks.index', compact('tasks'));
    }

    public function create(Request $request): View
    {
        return view('tasks.form', [
            'task' => null,
            'goals' => $request->user()->goals()->orderBy('title')->get(),
            'defaults' => $this->reminderDefaults($request),
        ]);
    }

    public function store(Request $request, TaskPlannerService $planner): RedirectResponse
    {
        $data = $this->validated($request);
        $task = $request->user()->taskTemplates()->create([
            ...$data,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => (int) $request->user()->taskTemplates()->max('sort_order') + 1,
        ]);
        $planner->ensureDay($request->user(), now($request->user()->timezone)->toImmutable());

        return redirect()->route('tasks.index')->with('status', "{$task->name} added.");
    }

    public function edit(Request $request, int $task): View
    {
        return view('tasks.form', [
            'task' => $request->user()->taskTemplates()->findOrFail($task),
            'goals' => $request->user()->goals()->orderBy('title')->get(),
            'defaults' => $this->reminderDefaults($request),
        ]);
    }

    public function update(Request $request, int $task): RedirectResponse
    {
        $template = $request->user()->taskTemplates()->findOrFail($task);
        $template->update($this->validated($request));
        $user = $request->user();
        $today = now($user->timezone)->toImmutable()->startOfDay();
        $todayLog = $user->taskLogs()->where('task_template_id', $template->id)
            ->whereDate('date', $today->toDateString())->where('status', 'pending')->first();
        if ($todayLog && in_array($today->dayOfWeek, $template->weekdays ?? [], true)) {
            $todayLog->update([
                'scheduled_at' => CarbonImmutable::parse($today->toDateString().' '.$template->start_time, $user->timezone)->utc(),
            ]);
        } elseif ($todayLog) {
            $todayLog->delete();
        }

        return redirect()->route('tasks.index')->with('status', 'Task routine updated. Past days were left as they were.');
    }

    public function toggle(Request $request, int $task): RedirectResponse
    {
        $template = $request->user()->taskTemplates()->findOrFail($task);
        $template->update(['is_active' => ! $template->is_active]);

        return redirect()->route('tasks.index')->with('status', $template->is_active ? 'Task turned on.' : 'Task turned off.');
    }

    public function move(Request $request, int $task): RedirectResponse
    {
        $data = $request->validate(['direction' => ['required', 'in:up,down']]);
        $templates = $request->user()->taskTemplates()->orderBy('sort_order')->orderBy('id')->get();
        $index = $templates->search(fn ($template) => $template->id === $task);
        abort_if($index === false, 404);
        $targetIndex = $data['direction'] === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex >= 0 && $targetIndex < $templates->count()) {
            $current = $templates[$index];
            $target = $templates[$targetIndex];
            [$current->sort_order, $target->sort_order] = [$target->sort_order, $current->sort_order];
            $current->save();
            $target->save();
        }

        return back()->with('status', 'Routine order updated.');
    }

    public function resetDefaults(Request $request, TaskPlannerService $planner): RedirectResponse
    {
        $planner->seedDefaults($request->user());
        $planner->ensureDay($request->user(), now($request->user()->timezone)->toImmutable());

        return back()->with('status', 'Missing default routines were added. Existing history was kept.');
    }

    public function destroy(Request $request, int $task): RedirectResponse
    {
        $user = $request->user();
        $template = $user->taskTemplates()->findOrFail($task);
        $today = now($user->timezone)->toDateString();

        $user->taskLogs()
            ->where('task_template_id', $template->id)
            ->where(function ($query) use ($today) {
                $query->whereDate('date', '>', $today)
                    ->orWhere(function ($query) use ($today) {
                        $query->whereDate('date', $today)->where('scheduled_at', '>', now('UTC'));
                    });
            })->delete();

        $template->delete();

        return redirect()->route('tasks.index')->with('status', 'Task removed from the future. Past task history was kept.');
    }

    public function status(Request $request, int $taskLog, TaskPlannerService $planner): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,done,minimum,skipped']]);
        $log = $request->user()->taskLogs()->findOrFail($taskLog);
        $planner->updateStatus($request->user(), $log, $data['status']);

        return redirect()->route('home');
    }

    /** @return array{name:string,category:string,start_time:string,duration_minutes:int,weekdays:array<int,int>,minimum_version:?string,goal_id:?int} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'category' => ['required', 'in:faith,health,coding,study,other'],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'weekdays' => ['required', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['required', 'integer', 'between:0,6', 'distinct'],
            'minimum_version' => ['nullable', 'string', 'max:500'],
            'remind_minutes_before' => ['sometimes', 'nullable', 'integer', 'between:0,1440'],
            'alarm_enabled' => ['sometimes', 'boolean'],
            'goal_id' => ['nullable', Rule::exists('goals', 'id')->where('user_id', $request->user()->id)->whereNull('deleted_at')],
        ]);
        $data['weekdays'] = array_map('intval', $data['weekdays']);
        $data['remind_minutes_before'] = (int) ($data['remind_minutes_before'] ?? $this->reminderDefaults($request)['minutes']);
        $data['alarm_enabled'] = isset($data['alarm_enabled']);

        return $data;
    }

    /** @return array{minutes:int,alarm:bool} */
    private function reminderDefaults(Request $request): array
    {
        $settings = $request->user()->settings();

        return [
            'minutes' => (int) ($settings->where('key', 'default_remind_minutes_before')->value('value') ?? 5),
            'alarm' => filter_var($settings->where('key', 'default_alarm_enabled')->value('value') ?? false, FILTER_VALIDATE_BOOL),
        ];
    }
}
