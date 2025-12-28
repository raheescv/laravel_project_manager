<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackagePayment extends Model
{
    protected $fillable = ['package_id', 'amount', 'payment_method_id', 'date', 'created_by', 'updated_by'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge(
            [
                'package_id' => ['required', 'exists:packages,id'],
                'amount' => ['required', 'numeric', 'min:0'],
                'payment_method_id' => ['required', 'exists:accounts,id'],
                'date' => ['required', 'date'],
            ],
            $merge,
        );
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
