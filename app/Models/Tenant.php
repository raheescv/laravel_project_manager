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
    ];

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
        };

        static::saved($forget);
        static::deleted($forget);
        static::restored($forget);
    }

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique(self::class, 'code')->ignore($id)],
            'subdomain' => ['required', 'string', 'max:255', Rule::unique(self::class, 'subdomain')->ignore($id)],
            'domain' => ['nullable', 'string', 'max:255'],
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
     * The tenant's own workspace address, built from the SUBDOMAIN because that
     * is the only thing IdentifyTenant resolves a host by — `domain` is never
     * read there, so trusting it could point at another tenant's workspace.
     * The subdomain replaces the first label of the app host (acme.test,
     * acme.example.com); a bare host such as localhost gets it prefixed.
     */
    public function url(string $path = ''): string
    {
        $appUrl = config('app.url');
        $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?: 'https';
        $port = parse_url($appUrl, PHP_URL_PORT);

        $labels = explode('.', (string) parse_url($appUrl, PHP_URL_HOST));
        $replaceFirstLabel = count($labels) >= 3 || (count($labels) === 2 && in_array($labels[1], ['test', 'local'], true));
        $host = $replaceFirstLabel
            ? implode('.', [$this->subdomain, ...array_slice($labels, 1)])
            : $this->subdomain.'.'.implode('.', $labels);

        return $scheme.'://'.$host.($port ? ':'.$port : '').'/'.ltrim($path, '/');
    }
}
