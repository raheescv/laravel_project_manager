<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'subdomain',
        'domain',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'domain_synced_at' => 'datetime',
    ];

    public const DOMAIN_PENDING = 'pending';

    public const DOMAIN_ACTIVE = 'active';

    public const DOMAIN_FAILED = 'failed';

    /**
     * IdentifyTenant resolves hosts through a 24h cache of active tenants, so a
     * renamed, deactivated or deleted tenant must drop its entries at once —
     * otherwise a switched-off tenant keeps serving requests for a day.
     */
    protected static function booted(): void
    {
        $forget = function (Tenant $tenant): void {
            foreach (array_unique(array_filter([$tenant->subdomain, $tenant->getOriginal('subdomain')])) as $subdomain) {
                Cache::forget("tenant_subdomain_{$subdomain}");
            }
            foreach (array_unique(array_filter([$tenant->domain, $tenant->getOriginal('domain')])) as $domain) {
                Cache::forget("tenant_domain_{$domain}");
            }
        };

        // A changed custom domain (or a tenant going away) is picked up by the
        // root-run tenant:server-sync, which writes or removes its nginx site.
        static::saving(function (Tenant $tenant): void {
            // Only touch the sync columns when there is something to record: the
            // migration that seeds the first tenant runs before they exist.
            if ($tenant->isDirty('domain') && ($tenant->hasCustomDomain() || $tenant->getOriginal('domain_status') !== null)) {
                $tenant->domain_status = $tenant->hasCustomDomain() ? self::DOMAIN_PENDING : null;
                $tenant->domain_error = null;
            } elseif ($tenant->isDirty('is_active') && $tenant->hasCustomDomain()) {
                $tenant->domain_status = self::DOMAIN_PENDING;
            }
        });
        $markForRemoval = function (Tenant $tenant): void {
            if ($tenant->hasCustomDomain()) {
                $tenant->newQueryWithoutScopes()->whereKey($tenant->id)->update(['domain_status' => self::DOMAIN_PENDING]);
            }
        };

        static::saved($forget);
        static::deleted($forget);
        static::deleted($markForRemoval);
        static::restored($forget);
        static::restored($markForRemoval);
    }

    /**
     * Normalise whatever was typed ("https://Shop.Example.com/") to a bare host.
     */
    public function setDomainAttribute(?string $value): void
    {
        $host = strtolower(trim((string) $value));
        $host = (string) preg_replace('#^[a-z]+://#', '', $host);
        $this->attributes['domain'] = rtrim(explode('/', $host)[0], '.') ?: null;
    }

    /**
     * A domain that needs its own nginx site: set, and not already covered by
     * the app host or this tenant's wildcard subdomain address.
     */
    public function hasCustomDomain(): bool
    {
        if (blank($this->domain)) {
            return false;
        }

        return ! in_array($this->domain, [parse_url(config('app.url'), PHP_URL_HOST), parse_url($this->url(), PHP_URL_HOST)], true);
    }

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique(self::class, 'code')->ignore($id)],
            'subdomain' => ['required', 'string', 'max:255', Rule::unique(self::class, 'subdomain')->ignore($id)],
            'domain' => [
                'nullable', 'string', 'max:255',
                'regex:/^(https?:\/\/)?([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}\/?$/i',
                Rule::notIn([parse_url(config('app.url'), PHP_URL_HOST)]),
                Rule::unique(self::class, 'domain')->ignore($id),
            ],
            'is_active' => ['boolean'],
        ], $merge);
    }

    /**
     * Get all branches belonging to this tenant
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'tenant_id');
    }

    /**
     * Get all users belonging to this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Get all products belonging to this tenant
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    /**
     * Get all sales belonging to this tenant
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'tenant_id');
    }

    /**
     * Get all purchases belonging to this tenant
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'tenant_id');
    }

    /**
     * Get all accounts belonging to this tenant
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'tenant_id');
    }

    /**
     * Get all journals belonging to this tenant
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'tenant_id');
    }

    /**
     * Get all inventories belonging to this tenant
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'tenant_id');
    }

    /**
     * The tenant's own workspace address, always built from the SUBDOMAIN: the
     * wildcard site serves it with no per-tenant setup, whereas a custom domain
     * only works once its DNS and certificate are in place (tenant:server-sync).
     */
    public function url(string $path = ''): string
    {
        $appUrl = config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);
        $host = $this->subdomain.self::subdomainSuffix();

        return $scheme.'://'.$host.($port ? ':'.$port : '').'/'.ltrim($path, '/');
    }

    /**
     * What follows the subdomain in a tenant's address: ".test" for
     * app.test, ".example.com" for app.example.com, ".localhost" for localhost.
     */
    public static function subdomainSuffix(): string
    {
        $labels = explode('.', (string) parse_url(config('app.url'), PHP_URL_HOST));
        $replaceFirstLabel = count($labels) >= 3 || (count($labels) === 2 && in_array($labels[1], ['test', 'local'], true));

        return '.'.implode('.', $replaceFirstLabel ? array_slice($labels, 1) : $labels);
    }
}
