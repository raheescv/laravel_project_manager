<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A pre-order paid for one date, and the sale that paid it (ChargeAction). Deleted when refunded. */
class StudentPreOrderCollection extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'student_pre_order_id',
        'account_id',
        'date',
        'sale_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /** @return BelongsTo<StudentPreOrder, $this> */
    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(StudentPreOrder::class, 'student_pre_order_id');
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
