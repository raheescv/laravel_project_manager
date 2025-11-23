<?php

namespace App\Http\Middleware;

use Closure;
use Inertia\Inertia;

class ChooseInertiaRootView
{
    public function handle($request, Closure $next)
    {
        // All inventory pages use React
        if ($request->routeIs('inventory.*')) {
            Inertia::setRootView('app-react');
        }
        // Everything else uses Vue
        else {
            Inertia::setRootView('app');
        }

        return $next($request);
    }
}
