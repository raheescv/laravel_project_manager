<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Purchase extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'branch_id',
        'account_id',
        'date',
        'delivery_date',
        'gross_amount',
        'item_discount',
        'tax_amount',
        'total',
        'other_discount',
        'freight',
        'paid',
        'address',
        'status',
        'created_by',
        'updated_by',
        'cancelled_by',
        'deleted_by',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'date' => ['required'],
            'invoice_no' => ['required'],
        ], $merge);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function journal()
    {
        return $this->hasOne(Journal::class, 'model_id')->where('model', 'Purchase');
    }
}
