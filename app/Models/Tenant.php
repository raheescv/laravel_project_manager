<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
}
