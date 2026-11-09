<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlannerIdleLock
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->routeIs('lock.show', 'lock.unlock', 'logout')) {
            return $next($request);
        }

        $timeout = (int) ($request->user()->settings()->where('key', 'lock_timeout_minutes')->value('value') ?: 10);
        $now = now('UTC')->timestamp;
        $lastActivity = (int) $request->session()->get('planner_last_activity', $now);
        $locked = $request->session()->get('planner_locked', false);

        if ($locked || $now - $lastActivity >= $timeout * 60) {
            $request->session()->put('planner_locked', true);
            $request->session()->forget('planner_last_activity');

            return redirect()->route('lock.show');
        }

        $request->session()->put('planner_last_activity', $now);

        return $next($request);
    }
}
