<?php

namespace App\Actions\User;

use App\Models\UserAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceAction
{
    public function execute($date, $attendance)
    {
        try {
            // Delete existing attendance records for the date
            if (UserAttendance::where('date', $date)->count() > 0 && ! Auth::user()->can('employee attendance.modify')) {
                throw new \Exception('You do not have permission to modify attendance records.');
            }
            UserAttendance::where('date', $date)->delete();

            $now = Carbon::now();
            $data = [];
            foreach ($attendance as $employee_id => $value) {
                if ($value) {
                    $data[] = [
                        'employee_id' => $employee_id,
                        'date' => $date,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($data)) {
                UserAttendance::insert($data);
            }

            $return['success'] = true;
            $return['message'] = 'Attendance updated successfully';
            $return['data'] = [];
        } catch (\Throwable $th) {
            $return['success'] = false;
            $return['message'] = 'Error updating attendance: '.$th->getMessage();
        }

        return $return;
    }
}
