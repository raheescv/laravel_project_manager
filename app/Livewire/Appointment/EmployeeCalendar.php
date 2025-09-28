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

    public $status = 'pending';

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
            ->when($this->employee_id, fn ($query, $value) => $query->whereIn('employee_id', $value))
            ->whereHas('appointment', function ($query): void {
                $query->when($this->status, fn ($query, $value) => $query->where('status', $value));
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
        $limit = $this->employee_id ? 20 : 10;
        $employeeIds = AppointmentItem::pluck('employee_id')->toArray();

        return User::employee()
            ->select(['id', 'name'])
            ->whereHas('branches', fn ($query) => $query->where('user_has_branches.branch_id', session('branch_id')))
            ->when($this->employee_id, fn ($query) => $query->whereIn('id', $this->employee_id))
            ->whereIn('id', $employeeIds)
            ->limit($limit)
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
