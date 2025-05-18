<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class UserAttendance extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'date',
        'employee_id',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
