<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($payment) {
            if ($payment->package) {
                $payment->package->updatePaidAmount();
            }
        });

        static::deleted(function ($payment) {
            if ($payment->package) {
                $payment->package->updatePaidAmount();
            }
        });
    }
}
