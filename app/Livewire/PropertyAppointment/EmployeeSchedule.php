<?php

namespace App\Livewire\PropertyAppointment;

use App\Actions\PropertyAppointment\Availability\CreateAction;
use App\Actions\PropertyAppointment\Availability\CreateDefaultsAction;
use App\Actions\PropertyAppointment\Availability\DeleteAction;
use App\Actions\PropertyAppointment\TimeOff\CreateAction as TimeOffCreateAction;
use App\Actions\PropertyAppointment\TimeOff\DeleteAction as TimeOffDeleteAction;
use App\Models\PropertyAppointment;
use App\Models\PropertyAppointmentAvailability;
use App\Models\PropertyAppointmentTimeOff;
use App\Models\WorkingDay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * One employee's appointment schedule, embedded on the shared User/Employee view
 * page. Shows their upcoming appointments and lets an authorised user maintain the
 * weekly availability those appointments are booked against.
 */
class EmployeeSchedule extends Component
{
    public $userId;

    public $day_of_week = 0;

    public $start_time = '09:00';

    public $end_time = '13:00';

    public $off_date;

    public $off_start_time;

    public $off_end_time;

    public $off_reason;

    protected $listeners = [
        'PropertyAppointment-Refresh-Component' => '$refresh',
    ];

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->off_date = date('Y-m-d');
    }

    public function addAvailability()
    {
        abort_unless(auth()->user()?->can('property appointment.manage availability'), 403);
        $this->run(fn () => (new CreateAction())->execute([
            'user_id' => $this->userId,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ], Auth::id()));
    }

    /** Copy the company working week onto this employee in one press. */
    public function addDefaultHours()
    {
        abort_unless(auth()->user()?->can('property appointment.manage availability'), 403);
        $this->run(fn () => (new CreateDefaultsAction())->execute($this->userId, Auth::id()));
    }

    public function removeAvailability($id)
    {
        abort_unless(auth()->user()?->can('property appointment.manage availability'), 403);
        $this->run(fn () => (new DeleteAction())->execute($id, Auth::id()));
    }

    public function addTimeOff()
    {
        abort_unless(auth()->user()?->can('property appointment.manage availability'), 403);
        $this->run(fn () => (new TimeOffCreateAction())->execute([
            'user_id' => $this->userId,
            'date' => $this->off_date,
            'start_time' => $this->off_start_time ?: null,
            'end_time' => $this->off_end_time ?: null,
            'reason' => $this->off_reason ?: null,
        ], Auth::id()));
    }

    public function removeTimeOff($id)
    {
        abort_unless(auth()->user()?->can('property appointment.manage availability'), 403);
        $this->run(fn () => (new TimeOffDeleteAction())->execute($id, Auth::id()));
    }

    private function run(callable $callback): void
    {
        try {
            DB::beginTransaction();
            $response = $callback();
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.property-appointment.employee-schedule', [
            'availabilities' => PropertyAppointmentAvailability::where('user_id', $this->userId)
                ->orderBy('day_of_week')->orderBy('start_time')->get()->groupBy('day_of_week'),
            'timeOffs' => PropertyAppointmentTimeOff::where('user_id', $this->userId)
                ->whereDate('date', '>=', now()->subWeek())->orderBy('date')->get(),
            'upcoming' => PropertyAppointment::with(['customer:id,name', 'rentOut:id,property_id', 'rentOut.property:id,number'])
                ->where('employee_id', $this->userId)
                ->upcoming()
                ->orderBy('scheduled_at')
                ->limit(15)
                ->get(),
            'days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            // The company week from Settings -> Working Day. It is what this
            // employee is already bookable on while the panel above is empty,
            // so the view can state the real hours instead of warning about none.
            'companyHours' => WorkingDay::schedule(),
        ]);
    }
}
