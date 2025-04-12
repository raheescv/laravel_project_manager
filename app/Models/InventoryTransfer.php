<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class InventoryTransfer extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'date',
        'branch_id',
        'from_branch_id',
        'to_branch_id',
        'description',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'updated_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'date' => ['required'],
        ], $merge);
    }

    public static function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public static function scopeCurrentBranch($query)
    {
        return $query->where('branch_id', session('branch_id'));
    }

    public function items()
    {
        return $this->hasMany(InventoryTransferItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }
}
