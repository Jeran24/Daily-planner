<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectsController extends Controller
{
    public function index(Request $request): View
    {
        $projects = $request->user()->projects()->orderBy('priority')->orderByDesc('updated_at')->get();

        return view('goals.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('goals.projects.form', ['project' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->projects()->create($this->validated($request));

        return redirect()->route('goals.projects')->with('status', 'Project added.');
    }

    public function edit(Request $request, int $project): View
    {
        return view('goals.projects.form', ['project' => $request->user()->projects()->findOrFail($project)]);
    }

    public function update(Request $request, int $project): RedirectResponse
    {
        $request->user()->projects()->findOrFail($project)->update($this->validated($request));

        return redirect()->route('goals.projects')->with('status', 'Project updated.');
    }

    public function destroy(Request $request, int $project): RedirectResponse
    {
        $request->user()->projects()->findOrFail($project)->delete();

        return redirect()->route('goals.projects')->with('status', 'Project archived.');
    }

    /** @return array{name:string,description:?string,skills_needed:array<int,string>,status:string,priority:int,notes:?string} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'skills_needed' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:idea,planned,active,done'],
            'priority' => ['required', 'integer', 'between:1,5'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
        $data['skills_needed'] = collect(preg_split('/\R/', $data['skills_needed'] ?? '') ?: [])
            ->map(fn ($skill) => trim($skill))->filter()->unique()->values()->all();

        return $data;
    }
}
