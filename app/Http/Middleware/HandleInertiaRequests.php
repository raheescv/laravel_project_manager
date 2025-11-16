<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The default root template loaded on first page visit.
     *
     * @var string
     */
    protected $rootView = 'app'; // default Vue root

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
        ];
    }

    /**
     * Dynamically change the root view for React pages.
     */
    public function rootView(Request $request): string
    {
        // For inventory React pages
        if ($request->is('inventory*')) {
            return 'app-react';
        }

        // For scan React pages
        if ($request->is('scan*')) {
            return 'app-react';
        }

        // Default root
        return $this->rootView;
    }
}
