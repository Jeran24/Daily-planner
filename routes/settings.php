<?php

use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'planner.idle-lock'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'planner.idle-lock'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        /* @chisel-password-confirmation */
        ->middleware([
            'password.confirm',
        ])
        /* @end-chisel-password-confirmation */
        ->name('security.edit');
});

Route::middleware(['auth', 'planner.idle-lock'])->group(function () {
    Route::get('settings/reminders', [SettingsController::class, 'reminders'])->name('settings.reminders');
    Route::post('settings/reminders', [SettingsController::class, 'updateReminders'])->name('settings.reminders.update');
    Route::get('settings/goals', [SettingsController::class, 'goals'])->name('settings.goals');
    Route::post('settings/goals', [SettingsController::class, 'updateGoals'])->name('settings.goals.update');
    Route::get('settings/privacy', [SettingsController::class, 'privacy'])->name('settings.privacy');
    Route::post('settings/privacy', [SettingsController::class, 'updatePrivacy'])->name('settings.privacy.update');
    Route::delete('settings/privacy/devices/{session}', [SettingsController::class, 'revokeDevice'])->name('settings.devices.destroy');
    Route::get('settings/data', [SettingsController::class, 'data'])->name('settings.data');
    Route::get('settings/data/export.json', [SettingsController::class, 'exportJson'])->name('settings.export.json');
    Route::get('settings/data/export.csv', [SettingsController::class, 'exportCsv'])->name('settings.export.csv');
    Route::delete('settings/data/account', [SettingsController::class, 'deleteAccount'])->name('settings.account.delete');
});

/* @chisel-passkeys */
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
/* @end-chisel-passkeys */
