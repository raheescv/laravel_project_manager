<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "Switch into tenant" from Tenant Control.
 *
 * Tenants are resolved by host and sessions are per host, so the super admin
 * cannot simply change a session value: they are handed to the tenant's own
 * address with a random, single-use, short-lived token. Redeeming it there
 * opens a time-boxed impersonation of that tenant's admin (banner, audit log
 * and auto-expiry all come from ImpersonationService), and leaving it sends
 * them back to their own workspace.
 */
class TenantSwitchService
{
    public const TOKEN_TTL_SECONDS = 120;

    public function __construct(private readonly ImpersonationService $impersonation) {}

    /**
     * The user the super admin will act as: the tenant's longest-standing
     * active admin, falling back to its first active user.
     */
    public function targetUserFor(Tenant $tenant): ?User
    {
        return User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_admin')
            ->orderBy('id')
            ->first();
    }

    /**
     * @throws \Exception when the tenant cannot be entered
     */
    public function entryUrl(Tenant $tenant, User $superAdmin, string $returnUrl): string
    {
        if (! $superAdmin->is_super_admin) {
            throw new \Exception('Only super admins can switch into a tenant.', 1);
        }
        if (! $tenant->is_active) {
            throw new \Exception('Activate the tenant before switching into it.', 1);
        }
        if ($tenant->id === $superAdmin->tenant_id) {
            throw new \Exception('You are already signed into this tenant.', 1);
        }

        $host = parse_url($tenant->url(), PHP_URL_HOST);
        if (app()->bound('request') && $host === request()->getHost()) {
            throw new \Exception("{$tenant->name} resolves to this same address ({$host}). Give it its own subdomain first.", 1);
        }

        $target = $this->targetUserFor($tenant);
        if (! $target) {
            throw new \Exception('This tenant has no active user to sign in as. Create an admin from the Seeding tab first.', 1);
        }

        $token = Str::random(64);
        Cache::put($this->cacheKey($token), [
            'tenant_id' => $tenant->id,
            'impersonator_id' => $superAdmin->id,
            'target_user_id' => $target->id,
            'return_url' => $returnUrl,
        ], now()->addSeconds(self::TOKEN_TTL_SECONDS));

        Log::info('Tenant switch issued', ['impersonator_id' => $superAdmin->id, 'tenant_id' => $tenant->id, 'target_user_id' => $target->id]);

        return $tenant->url('tenants/enter/'.$token);
    }

    /**
     * Redeem a token on the tenant's own host. Returns null when the token is
     * unknown, used, expired, or was issued for a different tenant than the
     * host being visited.
     */
    public function enter(string $token, ?Tenant $hostTenant): ?User
    {
        $payload = Cache::pull($this->cacheKey($token));
        if (! $payload || ! $hostTenant || $hostTenant->id !== $payload['tenant_id']) {
            return null;
        }

        $impersonator = User::withoutGlobalScopes()->find($payload['impersonator_id']);
        $target = User::withoutGlobalScopes()->where('tenant_id', $hostTenant->id)->where('is_active', true)->find($payload['target_user_id']);
        if (! $impersonator?->is_super_admin || ! $target) {
            return null;
        }

        $this->impersonation->startAs($impersonator->id, $target, $payload['return_url']);

        return $target;
    }

    private function cacheKey(string $token): string
    {
        return 'tenant_switch:'.hash('sha256', $token);
    }
}
