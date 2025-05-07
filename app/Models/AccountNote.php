<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class AccountNote extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'note',
        'type',
        'follow_up_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'account_id' => ['required'],
            'note' => ['required'],
            'type' => ['required', 'in:'.implode(',', array_keys(noteTypes()))],
            'follow_up_date' => ['nullable', 'date'],
            'status' => ['required', 'in:pending,completed'],
            'created_by' => ['required'],
            'updated_by' => ['required'],
        ], $merge);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
