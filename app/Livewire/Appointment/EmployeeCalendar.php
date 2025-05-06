<?php

namespace App\Livewire\Appointment;

use App\Models\AppointmentItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class EmployeeCalendar extends Component
{
    public $appointments;

    public $employee_id;

    public $date;

    protected $listeners = [
        'Refresh-EmployeeCalendar-Component' => '$refresh',
    ];

    public function mount(): void
    {
        $this->date = date('Y-m-d');
    }

    public function loadAppointments(): void
    {
        $this->appointments = AppointmentItem::query()
            ->with([
                'employee:id,name',
                'service:id,name',
                'appointment:id,start_time,end_time,color',
            ])
            ->when($this->employee_id, fn ($query) => $query->whereIn('employee_id', $this->employee_id))
            ->whereHas('appointment', function ($query) {
                // $query->whereDate('start_time', $this->date);
            })
            ->get()
            ->map(fn ($item) => [
                'id' => $item->appointment_id,
                'title' => $item->service?->name,
                'start' => $item->appointment->start_time,
                'end' => $item->appointment->end_time,
                'key' => $item->service_id.'-'.$item->employee_id,
                'resourceId' => $item->employee_id,
                'backgroundColor' => $item->appointment?->color ?? '#3788d8',
                'borderColor' => $item->appointment?->color ?? '#3788d8',
                'status' => $item->status,
            ]);
    }

    public function getEvents(): Collection
    {
        $this->loadAppointments();

        return $this->appointments;
    }

    public function getResources(): array
    {
        return User::employee()
            ->select(['id', 'name'])
            ->whereHas('branches', fn ($query) => $query->where('user_has_branches.branch_id', session('branch_id'))
            )
            ->when($this->employee_id, fn ($query) => $query->whereIn('id', $this->employee_id)
            )
            ->get()
            ->map(fn ($employee) => [
                'id' => $employee->id,
                'title' => $employee->name,
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.appointment.employee-calendar');
    }
}
