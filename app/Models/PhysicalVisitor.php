<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhysicalVisitor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'name',
        'date_of_birth',
        'id_card_type',
        'id_card_number',
        'address',
        'phone',
        'email',
        'purpose_of_visit',
        'host_employee_id',
        'host_department',
        'check_in_time',
        'check_out_time',
        'status',
        'id_card_image_path',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function hostEmployee()
    {
        return $this->belongsTo(User::class, 'host_employee_id');
    }

    // Scopes
    public function scopeCheckedIn($query)
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeCheckedOut($query)
    {
        return $query->where('status', 'checked_out');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('check_in_time', Carbon::today());
    }

    // Methods
    public function checkOut()
    {
        $this->update([
            'check_out_time' => now(),
            'status' => 'checked_out',
        ]);
    }

    public function getDurationAttribute()
    {
        if (! $this->check_out_time) {
            return $this->check_in_time->diffForHumans();
        }

        return $this->check_in_time->diffForHumans($this->check_out_time);
    }

    public static function getVisitorStats($startDate = null, $endDate = null)
    {
        $query = self::query();

        if ($startDate && $endDate) {
            $query->whereBetween('check_in_time', [$startDate, $endDate]);
        }

        $totalVisitors = $query->count();
        $checkedIn = $query->checkedIn()->count();
        $checkedOut = $query->checkedOut()->count();

        return [
            'total_visitors' => $totalVisitors,
            'currently_checked_in' => $checkedIn,
            'checked_out' => $checkedOut,
        ];
    }
}
