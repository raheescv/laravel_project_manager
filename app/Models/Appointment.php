<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Appointment extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'account_id',
        'start_time',
        'end_time',
        'color',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public static function rules($id = null, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'account_id' => ['required'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ], $merge);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function items()
    {
        return $this->hasMany(AppointmentItem::class);
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
