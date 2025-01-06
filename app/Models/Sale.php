<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Sale extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'branch_id',
        'account_id',
        'date',
        'due_date',

        'customer_name',
        'customer_mobile',
        'reference_no',

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
        'cancelled_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'invoice_no' => ['required', Rule::unique(self::class, 'invoice_no')->ignore($id)],
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'date' => ['required'],
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

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }
}
