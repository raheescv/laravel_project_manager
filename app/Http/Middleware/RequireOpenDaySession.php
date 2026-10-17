<?php

namespace App\Http\Middleware;

use App\Livewire\General\BranchSelection;
use App\Models\SaleDaySession;
use Closure;
use Illuminate\Http\Request;

class RequireOpenDaySession
{
    /**
     * @param  string|null  $context  'pos' lets the POS through without an open
     *                                session when posSkipsDaySession() is on; a
     *                                branch is still required.
     */
    public function handle(Request $request, Closure $next, ?string $context = null)
    {
        // Check if the user has a default branch set
        if (! session('branch_id')) {
            // The branch popup opens there; picking a branch brings the user back here.
            session([BranchSelection::RETURN_TO => $request->fullUrl()]);

            return redirect()->route('sale::day-management')
                ->with('error', 'Please set a default branch before proceeding.');
        }

        if ($context === 'pos' && posSkipsDaySession()) {
            return $next($request);
        }

        // Check if the branch has an open day session
        if (! SaleDaySession::hasOpenSession(session('branch_id'))) {
            return redirect()->route('sale::day-management')
                ->with('error', 'Please open a day session for this branch before adding sales.');
        }

        return $next($request);
    }
}
