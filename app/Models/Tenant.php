<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
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
        'started_on',
        'renews_on',
        'amc_amount',
        'amc_cycle',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'domain_synced_at' => 'datetime',
        'started_on' => 'date',
        'renews_on' => 'date',
        'amc_amount' => 'decimal:2',
    ];

    /**
     * AMC billing cycles: label and length in months.
     *
     * @var array<string, array{0: string, 1: int}>
     */
    public const AMC_CYCLES = [
        'monthly' => ['Monthly', 1],
        'quarterly' => ['Quarterly', 3],
        'half_yearly' => ['Half-yearly', 6],
        'yearly' => ['Yearly', 12],
    ];

    /** Renewals this close (in days) are flagged as due soon. */
    public const RENEWAL_WARNING_DAYS = 30;

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
            } elseif ($tenant->isDirty(['is_active', 'subdomain']) && $tenant->hasCustomDomain()) {
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
     * the app host or — when the server has a wildcard site — this tenant's
     * subdomain address.
     */
    public function hasCustomDomain(): bool
    {
        if (blank($this->domain)) {
            return false;
        }

        $coveredHosts = [parse_url(config('app.url'), PHP_URL_HOST)];
        if (config('tenant_server.wildcard_site')) {
            $coveredHosts[] = parse_url($this->url(), PHP_URL_HOST);
        }

        return ! in_array($this->domain, $coveredHosts, true);
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
            'started_on' => ['nullable', 'date'],
            'renews_on' => ['nullable', 'date'],
            'amc_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'amc_cycle' => ['nullable', Rule::in(array_keys(self::AMC_CYCLES))],
        ], $merge);
    }

    /**
     * Get all branches belonging to this tenant
     *
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'tenant_id');
    }

    /**
     * Get all users belonging to this tenant
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    /**
     * Get all products belonging to this tenant
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    /**
     * Get all sales belonging to this tenant
     *
     * @return HasMany<Sale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'tenant_id');
    }

    /**
     * Payments this tenant made to the installation owner.
     *
     * @return HasMany<TenantPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class, 'tenant_id');
    }

    public function amcCycleLabel(): ?string
    {
        return self::AMC_CYCLES[$this->amc_cycle][0] ?? null;
    }

    /**
     * The renewal date one AMC cycle after $from (yearly when no cycle is set).
     */
    public function nextRenewalFrom(CarbonInterface $from): Carbon
    {
        $months = self::AMC_CYCLES[$this->amc_cycle][1] ?? 12;

        return Carbon::parse($from)->addMonthsNoOverflow($months);
    }

    /**
     * How far through the current AMC cycle today is (0–100), for the list's renewal bar.
     */
    public function renewalProgress(): ?int
    {
        if (! $this->renews_on) {
            return null;
        }
        $months = self::AMC_CYCLES[$this->amc_cycle][1] ?? 12;
        $cycleStart = $this->renews_on->copy()->subMonthsNoOverflow($months);
        $cycleDays = max(1, $cycleStart->diffInDays($this->renews_on));

        return (int) round(min(100, max(0, $cycleStart->diffInDays(today(), false) / $cycleDays * 100)));
    }

    /**
     * overdue | due (within RENEWAL_WARNING_DAYS) | ok, or null with no renewal date.
     */
    public function renewalState(): ?string
    {
        if (! $this->renews_on) {
            return null;
        }
        if ($this->renews_on->lt(today())) {
            return 'overdue';
        }

        return today()->diffInDays($this->renews_on) <= self::RENEWAL_WARNING_DAYS ? 'due' : 'ok';
    }

    /**
     * "Today", "3 days ago", "2 years ago", or "5 days from now" for a future start.
     */
    public function startedAgo(): ?string
    {
        if (! $this->started_on) {
            return null;
        }
        if ($this->started_on->isToday()) {
            return 'Today';
        }

        return $this->started_on->copy()->startOfDay()->diffForHumans(today(), CarbonInterface::DIFF_RELATIVE_TO_NOW);
    }

    /**
     * "Today", "in 12 days", "3 days overdue" — counted in whole days.
     */
    public function renewalCountdown(): ?string
    {
        if (! $this->renews_on) {
            return null;
        }
        $days = (int) today()->diffInDays($this->renews_on, false);

        return match (true) {
            $days === 0 => 'Today',
            $days > 0 => 'in '.$days.' '.str('day')->plural($days),
            default => abs($days).' '.str('day')->plural(abs($days)).' overdue',
        };
    }

    /**
     * Get all purchases belonging to this tenant
     *
     * @return HasMany<Purchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'tenant_id');
    }

    /**
     * Get all accounts belonging to this tenant
     *
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'tenant_id');
    }

    /**
     * Get all journals belonging to this tenant
     *
     * @return HasMany<Journal, $this>
     */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'tenant_id');
    }

    /**
     * Get all inventories belonging to this tenant
     *
     * @return HasMany<Inventory, $this>
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
