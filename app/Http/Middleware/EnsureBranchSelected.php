<?php

namespace App\Http\Middleware;

use App\Livewire\General\BranchSelection;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * For pages rendered outside the admin layout (Inertia, React, standalone) —
 * they can't show the branch selection popup, so send the user to the
 * dashboard where it opens, and bring them back once a branch is picked.
 */
class EnsureBranchSelected
{
    public function handle(Request $request, Closure $next)
    {
        if (! BranchSelection::isRequired()) {
            return $next($request);
        }

        session([BranchSelection::RETURN_TO => $request->fullUrl()]);

        if ($request->header('X-Inertia')) {
            return Inertia::location(route('dashboard'));
        }

        return redirect()->route('dashboard');
    }
}
