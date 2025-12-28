<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Package extends Model
{
    protected $fillable = [
        'package_category_id',
        'account_id',
        'start_date',
        'end_date',
        'remarks',
        'amount',
        'paid',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'package_category_id' => ['required', 'exists:package_categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['in_progress', 'completed', 'cancelled'])],
            'remarks' => ['nullable', 'string'],
        ], $merge);
    }

    public function packageCategory()
    {
        return $this->belongsTo(PackageCategory::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(PackageItem::class);
    }

    public function payments()
    {
        return $this->hasMany(PackagePayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Update the paid amount based on all payments
     *
     * @return bool
     */
    public function updatePaidAmount()
    {
        $this->update([
            'paid' => $this->payments()->sum('amount'),
        ]);

        return true;
    }
}
