<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Services\GoalProgressService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function index(Request $request, GoalProgressService $progress): View
    {
        $user = $request->user();
        $goals = $user->goals()->with('items')->orderBy('end_date')->get();
        foreach ($goals as $goal) {
            $goal->setAttribute('report', $progress->report($user, $goal));
        }

        return view('goals.index', compact('goals'));
    }

    public function create(): View
    {
        return view('goals.form', ['goal' => null]);
    }

    public function store(Request $request, GoalProgressService $progress): RedirectResponse
    {
        $data = $this->validateGoal($request);

        return DB::transaction(function () use ($request, $data, $progress): RedirectResponse {
            $goal = $request->user()->goals()->create([
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'measure_type' => $data['measure_type'],
                'target_value' => $data['target_value'] ?? null,
                'manual_percent' => $data['measure_type'] === 'manual' ? $data['manual_percent'] : null,
                'status' => 'active',
            ]);
            $this->syncChecklist($goal, $data['checklist_items'] ?? '');
            $progress->recordSnapshot($request->user(), $goal, CarbonImmutable::now($request->user()->timezone));

            return redirect()->route('goals')->with('status', 'Goal created.');
        });
    }

    public function edit(Request $request, int $goal): View
    {
        $goal = $request->user()->goals()->with('items')->findOrFail($goal);

        return view('goals.form', compact('goal'));
    }

    public function update(Request $request, int $goal, GoalProgressService $progress): RedirectResponse
    {
        $user = $request->user();
        $goal = $user->goals()->with('items')->findOrFail($goal);
        $data = $this->validateGoal($request);

        DB::transaction(function () use ($goal, $data, $progress, $user): void {
            $goal->update([
                'title' => $data['title'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'measure_type' => $data['measure_type'],
                'target_value' => $data['target_value'] ?? null,
                'manual_percent' => $data['measure_type'] === 'manual' ? $data['manual_percent'] : null,
                'status' => $data['status'] ?? $goal->status,
            ]);
            $this->syncChecklist($goal, $data['checklist_items'] ?? '');
            $progress->recordSnapshot($user, $goal, CarbonImmutable::now($user->timezone));
        });

        return redirect()->route('goals')->with('status', 'Goal updated.');
    }

    public function destroy(Request $request, int $goal): RedirectResponse
    {
        $user = $request->user();
        $goal = $user->goals()->findOrFail($goal);
        $today = CarbonImmutable::now($user->timezone)->toDateString();
        $endDate = $goal->start_date->toDateString() <= $today ? min($goal->end_date->toDateString(), $today) : $goal->end_date->toDateString();
        $goal->update(['end_date' => $endDate, 'status' => 'archived']);
        $user->taskTemplates()->withTrashed()->where('goal_id', $goal->id)->update(['goal_id' => null]);
        $goal->delete();

        return redirect()->route('goals')->with('status', 'Goal removed from future tracking. Past task history was kept.');
    }

    public function addItem(Request $request, int $goal): RedirectResponse
    {
        $ownerGoal = $request->user()->goals()->findOrFail($goal);
        abort_unless($ownerGoal->measure_type === 'checklist', 404);
        $data = $request->validate(['title' => ['required', 'string', 'max:120']]);

        $ownerGoal->items()->create([
            'title' => $data['title'],
            'sort_order' => (int) $ownerGoal->items()->max('sort_order') + 1,
        ]);

        return redirect()->route('goals.edit', $ownerGoal)->with('status', 'Checklist item added.');
    }

    public function toggleItem(Request $request, int $goal, int $item): RedirectResponse
    {
        $ownerGoal = $request->user()->goals()->findOrFail($goal);
        $item = $ownerGoal->items()->findOrFail($item);
        $item->update(['done_at' => $item->done_at ? null : now('UTC')]);

        return redirect()->route('goals.edit', $ownerGoal);
    }

    public function deleteItem(Request $request, int $goal, int $item): RedirectResponse
    {
        $ownerGoal = $request->user()->goals()->findOrFail($goal);
        $ownerGoal->items()->findOrFail($item)->delete();

        return redirect()->route('goals.edit', $ownerGoal)->with('status', 'Checklist item removed.');
    }

    /** @return array<string, mixed> */
    private function validateGoal(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'measure_type' => ['required', Rule::in(['checklist', 'minutes', 'task_count', 'manual'])],
            'target_value' => [Rule::requiredIf(fn () => in_array($request->input('measure_type'), ['minutes', 'task_count'], true)), 'nullable', 'integer', 'min:1', 'max:1000000'],
            'manual_percent' => [Rule::requiredIf(fn () => $request->input('measure_type') === 'manual'), 'nullable', 'numeric', 'between:0,100'],
            'checklist_items' => [Rule::requiredIf(fn () => $request->input('measure_type') === 'checklist'), 'nullable', 'string', 'max:10000'],
            'status' => ['sometimes', Rule::in(['active', 'paused', 'completed'])],
        ]);

        if ($data['measure_type'] === 'checklist' && $this->checklistLines($data['checklist_items'] ?? '') === []) {
            throw ValidationException::withMessages(['checklist_items' => 'Add at least one checklist item.']);
        }

        return $data;
    }

    private function syncChecklist(Goal $goal, string $input): void
    {
        if ($goal->measure_type !== 'checklist') {
            return;
        }

        $titles = $this->checklistLines($input);
        $existing = $goal->items()->get()->keyBy('title');
        foreach ($titles as $sort => $title) {
            if ($existing->has($title)) {
                $existing->pull($title)->update(['sort_order' => $sort]);
            } else {
                $goal->items()->create(['title' => $title, 'sort_order' => $sort]);
            }
        }
        $goal->items()->whereNotIn('title', $titles)->delete();
    }

    /** @return array<int, string> */
    private function checklistLines(string $input): array
    {
        return collect(preg_split('/\R/', $input) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }
}
