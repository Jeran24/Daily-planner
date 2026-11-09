<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->middleware(['auth', 'planner.idle-lock'])
    ->name('home');

Route::middleware(['auth', 'planner.idle-lock'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('calendar', CalendarController::class)->name('calendar');
    Route::get('goals', [GoalController::class, 'index'])->name('goals');
    Route::get('goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');
    Route::post('goals/{goal}/items', [GoalController::class, 'addItem'])->name('goals.items.store');
    Route::patch('goals/{goal}/items/{item}', [GoalController::class, 'toggleItem'])->name('goals.items.toggle');
    Route::delete('goals/{goal}/items/{item}', [GoalController::class, 'deleteItem'])->name('goals.items.destroy');
    Route::get('goals/projects', [ProjectsController::class, 'index'])->name('goals.projects');
    Route::get('goals/projects/create', [ProjectsController::class, 'create'])->name('goals.projects.create');
    Route::post('goals/projects', [ProjectsController::class, 'store'])->name('goals.projects.store');
    Route::get('goals/projects/{project}/edit', [ProjectsController::class, 'edit'])->name('goals.projects.edit');
    Route::put('goals/projects/{project}', [ProjectsController::class, 'update'])->name('goals.projects.update');
    Route::delete('goals/projects/{project}', [ProjectsController::class, 'destroy'])->name('goals.projects.destroy');
    Route::get('goals/reports', ReportsController::class)->name('goals.reports');
    Route::get('reminders/due', [ReminderController::class, 'due'])->name('reminders.due');
    Route::post('reminders/subscribe', [ReminderController::class, 'subscribe'])->name('reminders.subscribe');
    Route::delete('reminders/subscribe', [ReminderController::class, 'unsubscribe'])->name('reminders.unsubscribe');
    Route::get('reminders/test', [ReminderController::class, 'testReminder'])->name('reminders.test');
    Route::get('journal', [JournalController::class, 'index'])->name('journal');
    Route::post('journal', [JournalController::class, 'store'])->name('journal.store');
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::patch('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('tasks/reset-defaults', [TaskController::class, 'resetDefaults'])->name('tasks.reset-defaults');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('task-logs/{taskLog}/status', [TaskController::class, 'status'])->name('task-logs.status');
});

Route::middleware(['auth'])->group(function () {
    Route::get('lock', [SettingsController::class, 'lock'])->name('lock.show');
    Route::post('lock', [SettingsController::class, 'unlock'])->name('lock.unlock');
});

require __DIR__.'/settings.php';
require __DIR__.'/scheduler.php';
