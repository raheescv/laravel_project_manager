<?php

namespace App\Http\Middleware;

use App\Jobs\TrackVisitorJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Don't track assets, API calls, or when testing
        if ($this->shouldTrack($request)) {
            $agent = new Agent();
            $data = [
                'branch_id' => session('branch_id', 1),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'visited_at' => now(),
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name,
                'device_type' => $this->getDeviceType($agent),
            ];
            TrackVisitorJob::dispatch($data);
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        $path = $request->path();

        return ! $request->ajax()
            && ! $request->is('api/*')
            && ! $request->is('_debugbar/*')
            && ! $request->is('livewire/*')
            && ! str_contains($path, 'list')
            && ! str_contains($path, '.')  // Skip files with extensions (assets)
            && ! app()->runningUnitTests();
    }

    private function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        } elseif ($agent->isMobile()) {
            return 'mobile';
        } elseif ($agent->isDesktop()) {
            return 'desktop';
        } else {
            return 'other';
        }
    }
}
