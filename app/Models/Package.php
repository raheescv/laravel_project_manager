<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Package extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'package_category_id',
        'account_id',
        'start_date',
        'end_date',
        'remarks',
        'amount',
        'paid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'package_category_id' => ['required', 'exists:package_categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['in_progress', 'completed', 'cancelled'])],
            'remarks' => ['nullable', 'string'],
        ], $merge);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<PackageCategory, $this> */
    public function packageCategory(): BelongsTo
    {
        return $this->belongsTo(PackageCategory::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /** @return HasMany<PackageItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class);
    }

    /** @return HasMany<PackagePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PackagePayment::class);
    }

    /** @return HasMany<Journal, $this> */
    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'Package');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Update the paid amount based on all payments
     *
     * @return bool
     */
    public function updatePaidAmount()
    {
        $this->update([
            'paid' => $this->payments()->sum('amount'),
        ]);

        return true;
    }
}
