<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class TailoringOrderItemTailor extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'tailoring_order_item_id',
        'tailor_id',
        'tailor_commission',
        'completion_date',
        'rating',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tailor_commission' => 'decimal:2',
        'completion_date' => 'date:Y-m-d',
        'rating' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model): void {
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model): void {
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function tailoringOrderItem(): BelongsTo
    {
        return $this->belongsTo(TailoringOrderItem::class, 'tailoring_order_item_id');
    }

    public function tailor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tailor_id');
    }
}
