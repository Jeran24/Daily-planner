<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = CarbonImmutable::now($user->timezone)->toDateString();
        $data = $request->validate(['date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:'.$today]]);
        $date = $data['date'] ?? $today;
        $entry = $user->journalEntries()->where('date', $date)->first();

        return view('journal.index', compact('date', 'entry'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $today = CarbonImmutable::now($user->timezone)->toDateString();
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'mood' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $user->journalEntries()->updateOrCreate(
            ['date' => $data['date']],
            ['mood' => $data['mood'], 'body' => $data['body']],
        );

        return redirect()->route('journal', ['date' => $data['date']])->with('status', 'Journal entry saved.');
    }
}
