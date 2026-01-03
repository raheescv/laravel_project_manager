<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract subdomain from host
        $subdomain = $this->extractSubdomain($request->getHost());

        if (! $subdomain) {
            // No subdomain, allow access (for main domain or localhost)
            // You can customize this behavior
            return $next($request);
        }
        // Find tenant by subdomain
        $tenant = $this->tenantService->findTenantBySubdomain($subdomain);

        if (! $tenant) {
            abort(404, 'Tenant not found or inactive');
        }

        // Set the current tenant
        $this->tenantService->setCurrentTenant($tenant);

        // Add tenant to request for easy access
        $request->merge(['tenant' => $tenant]);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    /**
     * Extract subdomain from host
     */
    protected function extractSubdomain(string $host): ?string
    {
        $host = $this->removePort($host);

        if ($subdomain = $this->extractFromLocalhost($host)) {
            return $subdomain;
        }

        return $this->extractFromStandardHost($host);
    }

    /**
     * Remove port from host if present
     */
    protected function removePort(string $host): string
    {
        return explode(':', $host)[0];
    }

    /**
     * Extract subdomain from localhost variations
     */
    protected function extractFromLocalhost(string $host): ?string
    {
        if (! str_contains($host, 'localhost')) {
            return null;
        }

        $parts = explode('.', $host);

        // Handle tenant1.localhost format
        if (count($parts) >= 2 && $parts[1] === 'localhost') {
            return $parts[0] !== 'localhost' ? $parts[0] : null;
        }

        // Handle pure localhost with query parameter or header
        if ($host === 'localhost' || $host === '127.0.0.1') {
            return request()->query('tenant') ?? request()->header('X-Tenant-Subdomain');
        }

        return null;
    }

    /**
     * Extract subdomain from standard host format
     */
    protected function extractFromStandardHost(string $host): ?string
    {
        $parts = explode('.', $host);

        // Standard subdomain.domain.tld format
        if (count($parts) >= 3) {
            return $parts[0];
        }

        // Development domains (.test or .local)
        if (count($parts) === 2 && in_array($parts[1], ['test', 'local'])) {
            return $parts[0];
        }

        return null;
    }
}
