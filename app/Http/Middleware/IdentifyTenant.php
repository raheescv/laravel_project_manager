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
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        // Extract subdomain from host
        $subdomain = $this->extractSubdomain($request->getHost());

        if (! $subdomain) {
            // Routes flagged with the "required" mode (e.g. the public catalog API)
            // must never run without a resolved tenant: TenantScope would otherwise
            // apply no filter and the request would read across ALL tenants.
            abort_if($mode === 'required', 404, 'Tenant could not be identified.');

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

        // Device/LAN access hits the machine by its raw IP (e.g. 192.168.x.x),
        // where there is no subdomain to parse. Fall back to the explicit
        // ?tenant= / X-Tenant-Subdomain hint the mobile app already sends.
        if ($subdomain = $this->extractFromIp($host)) {
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
     * Resolve the tenant from the request hint when the host is a bare IP
     * address (LAN / device access), where no subdomain can be parsed.
     */
    protected function extractFromIp(string $host): ?string
    {
        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        return request()->query('tenant') ?? request()->header('X-Tenant-Subdomain');
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
