<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PurchasePayment extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'payment_method_id',
        'date',
        'amount',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'purchase_id' => ['required'],
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

    public function paymentMethod()
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }

    public function getNameAttribute()
    {
        return $this->paymentMethod?->name;
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

}
