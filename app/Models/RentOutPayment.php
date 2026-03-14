<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutPayment extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'date',
        'credit',
        'debit',
        'account_id',
        'source',
        'source_id',
        'group',
        'category',
        'payment_type',
        'remark',
        'voucher_no',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'credit' => 'decimal:2',
        'debit' => 'decimal:2',
    ];

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function scopeReceipts($query)
    {
        return $query->where('credit', '>', 0);
    }

    public function scopePayments($query)
    {
        return $query->where('debit', '>', 0);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
