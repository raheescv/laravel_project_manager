<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class StockCheck extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'title',
        'date',
        'description',
        'signature',
        'signed_by',
        'signed_at',
        'status',
        'created_by',
        'updated_by',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'tenant_id' => ['required'],
            'branch_id' => ['required'],
            'title' => ['required'],
            'date' => ['required'],
            'signature' => ['nullable'],
            'signed_by' => ['nullable', 'exists:users,id'],
            'signed_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(stockCheckStatuses()))],
            'created_by' => ['required', 'exists:users,id'],
            'updated_by' => ['required', 'exists:users,id'],
        ], $merge);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(StockCheckItem::class, 'stock_check_id');
    }
}
