<?php

namespace App\Http\Middleware;

use App\Models\SaleDaySession;
use Closure;
use Illuminate\Http\Request;

class RequireOpenDaySession
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the user has a default branch set
        if (! session('branch_id')) {
            return redirect()->route('sale::day-management')
                ->with('error', 'Please set a default branch before proceeding.');
        }

        // Check if the branch has an open day session
        if (! SaleDaySession::hasOpenSession(session('branch_id'))) {
            return redirect()->route('sale::day-management')
                ->with('error', 'Please open a day session for this branch before adding sales.');
        }

        return $next($request);
    }
}
