<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class AppointmentItem extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'appointment_id',
        'service_id',
        'employee_id',
        'created_by',
        'updated_by',
    ];

    public static function rules($data, $id = null, $merge = [])
    {
        return array_merge([
            'appointment_id' => ['required', 'exists:appointments,id'],
            'service_id' => [
                'required',
                'exists:products,id',
                Rule::unique('appointment_items')
                    ->where(function ($query) use ($data) {
                        return $query->where('appointment_id', $data['appointment_id'])
                            ->where('employee_id', $data['employee_id']);
                    })
                    ->ignore($id),
            ],
            'employee_id' => [
                'required',
                'exists:users,id',
                Rule::unique('appointment_items')
                    ->where(function ($query) use ($data) {
                        return $query->where('appointment_id', $data['appointment_id'])
                            ->where('service_id', $data['service_id']);
                    })
                    ->ignore($id),
            ],
            'updated_by' => ['required', 'exists:users,id'],
        ], $merge);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service()
    {
        return $this->belongsTo(Product::class, 'service_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
