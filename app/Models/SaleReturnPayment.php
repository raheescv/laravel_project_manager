<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class SaleReturnPayment extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'sale_return_id',
        'payment_method_id',
        'date',
        'amount',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'sale_return_id' => ['required'],
            'payment_method_id' => ['required'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric'],
            'created_by' => ['required'],
            'updated_by' => ['required'],
        ], $merge);
    }

    public function scopeToday($query)
    {
        return $query->where('date', date('Y-m-d'));
    }

    public function scopeLast7Days($query)
    {
        return $query->whereBetween('date', [date('Y-m-d', strtotime('-7 days')), date('Y-m-d')]);
    }

    /**
     * sale_return_payments has no tenant_id: narrow to payments of returns
     * visible through the SaleReturn model's tenant, branch and soft-delete scopes.
     */
    public function scopeWithinCurrentTenant($query)
    {
        return $query->whereIn('sale_return_payments.sale_return_id', SaleReturn::query()->select('sale_returns.id'));
    }

    /** @return BelongsTo<SaleReturn, $this> */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }

    public function getNameAttribute()
    {
        return $this->paymentMethod?->name;
    }
}
