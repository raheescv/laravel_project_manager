<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class StockCheck extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'title',
        'date',
        'description',
        'signature',
        'signed_by',
        'signed_at',
        'status',
        'created_by',
        'updated_by',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'tenant_id' => ['required'],
            'branch_id' => ['required'],
            'title' => ['required'],
            'date' => ['required'],
            'signature' => ['nullable'],
            'signed_by' => ['nullable', 'exists:users,id'],
            'signed_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(stockCheckStatuses()))],
            'created_by' => ['required', 'exists:users,id'],
            'updated_by' => ['required', 'exists:users,id'],
        ], $merge);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
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

    /** @return HasMany<StockCheckItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(StockCheckItem::class, 'stock_check_id');
    }
}
