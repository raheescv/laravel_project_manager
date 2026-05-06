<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetDepreciationSchedule extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_id',
        'journal_id',
        'period_no',
        'period_type',
        'schedule_date',
        'opening_book_value',
        'depreciation_amount',
        'accumulated_depreciation',
        'closing_book_value',
        'status',
        'posted_at',
        'posting_note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'posted_at' => 'datetime',
        'opening_book_value' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'closing_book_value' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
