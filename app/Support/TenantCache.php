<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Configuration;
use App\Services\TenantService;
use Illuminate\Support\Facades\Cache;

/**
 * Tenant-aware cache for per-tenant settings and lookup maps.
 *
 * Every key is suffixed with the current tenant id and resolved lazily on
 * first read, so one tenant's cached values can never be served to another
 * tenant. When no tenant is resolved the default is returned and nothing is
 * cached — a tenant-scoped value must never be computed without a tenant.
 *
 * Read via the tenant_cache() helper; invalidate via TenantCache::forget().
 */
class TenantCache
{
    /** Configuration keys cached as their raw string value. */
    protected const CONFIGURATION_KEYS = [
        'barcode_type', 'barcode_prefix', 'sale_type', 'purchase_type', 'mobile', 'email',
        'company_name', 'company_description', 'gst_no', 'google_review_url', 'country_id',
        'currency_code', 'currency_symbol', 'base_currency_code', 'storefront_primary_color',
        'nav_order',
    ];

    /** Configuration keys cached as their json_decoded value. */
    protected const JSON_CONFIGURATION_KEYS = ['payment_methods', 'theme_settings', 'currencies'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $tenantId = static::tenantId();
        if (! $tenantId) {
            return $default;
        }

        return Cache::remember(static::key($key, $tenantId), now()->addDay(), fn () => static::resolve($key)) ?? $default;
    }

    public static function forget(string $key): void
    {
        if ($tenantId = static::tenantId()) {
            Cache::forget(static::key($key, $tenantId));
        }
    }

    public static function key(string $key, ?int $tenantId = null): string
    {
        $tenantId ??= static::tenantId();

        return "{$key}:tenant:{$tenantId}";
    }

    protected static function tenantId(): ?int
    {
        return app(TenantService::class)->getCurrentTenantId();
    }

    protected static function resolve(string $key): mixed
    {
        if (in_array($key, self::CONFIGURATION_KEYS, true)) {
            return Configuration::where('key', $key)->value('value');
        }

        if (in_array($key, self::JSON_CONFIGURATION_KEYS, true)) {
            $value = Configuration::where('key', $key)->value('value');

            return $value ? json_decode($value, true) : null;
        }

        return match ($key) {
            'branches' => Branch::select('id', 'name')->get(),
            // Account carries TenantScope, so the map is doubly guarded: the
            // key is tenant-suffixed AND the query only sees the tenant's rows.
            'accounts_slug_id_map' => Account::where('is_locked', 1)->pluck('id', 'slug')->toArray(),
            'logo' => ($logo = Configuration::where('key', 'logo')->value('value')) ? asset($logo) : asset('assets/img/logo.svg'),
            default => null,
        };
    }
}
