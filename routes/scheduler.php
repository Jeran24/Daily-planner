<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::post('internal/scheduler/run', function (Request $request) {
    $secret = (string) config('services.scheduler.secret', '');
    abort_unless(app()->isProduction() && $secret !== '' && hash_equals($secret, (string) $request->bearerToken()), 404);

    Artisan::call('schedule:run');

    return response()->json(['status' => 'schedule completed']);
})->middleware('throttle:5,1')->withoutMiddleware(PreventRequestForgery::class)->name('scheduler.run');
