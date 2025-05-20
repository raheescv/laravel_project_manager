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
            $data = $this->prepareVisitorData($request, $agent);

            TrackVisitorJob::dispatch($data)->onQueue('visitors');
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        $excludedPaths = [
            'api/*',
            '_debugbar/*',
            'livewire/*',
            'assets/*',
            'storage/*',
            '*/list*',
            'list*',
            'login',
            'dashboard*',
            '*.js',
            '*.css',
            '*.png',
            '*.jpg',
            '*.ico',
            '*.svg',
        ];

        return ! $request->ajax()
            && ! collect($excludedPaths)->contains(fn ($path) => $request->is($path))
            && ! app()->runningUnitTests();
    }

    private function prepareVisitorData(Request $request, Agent $agent): array
    {
        return [
            'branch_id' => session('branch_id', 1),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 255),
            'url' => substr($request->fullUrl(), 0, 255),
            'visited_at' => now(),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'device_type' => $this->getDeviceType($agent),
        ];
    }

    private function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        }
        if ($agent->isMobile()) {
            return 'mobile';
        }
        if ($agent->isDesktop()) {
            return 'desktop';
        }

        return 'other';
    }
}
