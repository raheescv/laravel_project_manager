<?php

namespace App\Models;

use App\Enums\SupplyRequest\SupplyRequestStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SupplyRequest extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'date',
        'order_no',
        'contact_person',
        'property_id',
        'property_group_id',
        'property_building_id',
        'property_type_id',
        'type',
        'total',
        'other_charges',
        'grand_total',
        'payment_mode_id',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
        'accounted_by',
        'accounted_at',
        'final_approved_by',
        'final_approved_at',
        'completed_by',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => SupplyRequestStatus::class,
        'approved_at' => 'datetime',
        'accounted_at' => 'datetime',
        'final_approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public static function statusOptions(): array
    {
        return SupplyRequestStatus::values();
    }

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'date' => 'required',
            'property_id' => 'required',
        ], $merge);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(SupplyRequestImage::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SupplyRequestNote::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function accountant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounted_by');
    }

    public function finalApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_approved_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function paymentMode(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_mode_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopeFilter($query, $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($query, $term) {
                $term = trim($term);
                $query->where(function ($q) use ($term) {
                    $q->where('order_no', 'like', '%'.$term.'%')
                        ->orWhere('contact_person', 'like', '%'.$term.'%')
                        ->orWhereHas('property', fn ($q) => $q->where('number', 'like', '%'.$term.'%'))
                        ->orWhereHas('creator', fn ($q) => $q->where('name', 'like', '%'.$term.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['type'] ?? null, fn ($query, $value) => $query->where('type', $value))
            ->when($filters['branch_id'] ?? null, fn ($query, $value) => $query->where('branch_id', $value))
            ->when($filters['property_id'] ?? null, fn ($query, $value) => $query->where('property_id', $value))
            ->when($filters['property_group_id'] ?? null, fn ($query, $value) => $query->where('property_group_id', $value))
            ->when($filters['property_building_id'] ?? null, fn ($query, $value) => $query->where('property_building_id', $value))
            ->when($filters['property_type_id'] ?? null, fn ($query, $value) => $query->where('property_type_id', $value))
            ->when($filters['created_by'] ?? null, fn ($query, $value) => $query->where('created_by', $value))
            ->when($filters['approved_by'] ?? null, fn ($query, $value) => $query->where('approved_by', $value))
            ->when($filters['final_approved_by'] ?? null, fn ($query, $value) => $query->where('final_approved_by', $value))
            ->when($filters['from_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '>=', date('Y-m-d', strtotime($value))))
            ->when($filters['to_date'] ?? null, fn ($query, $value) => $query->whereDate('date', '<=', date('Y-m-d', strtotime($value))));
    }
}
