<?php

namespace App\Livewire\User\Attendance;

use App\Actions\User\AttendanceAction;
use App\Models\User;
use App\Models\UserAttendance;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Page extends Component
{
    public $employees;

    public $dayList;

    public $date;

    public $selectAll = false;

    public $attendance = [];

    public $stats = [];

    protected $listeners = [
        'Create-Attendance-Page-Component' => 'create',
    ];

    public function mount($date = null)
    {
        $this->date = $date ?: date('Y-m-d');
        $this->loadEmployees();
        $this->getList();
        $this->calculateStats();
    }

    public function updated($key)
    {
        if (preg_match('/^attendance\..*/', $key)) {
            $this->calculateStats();
        }
    }

    public function create()
    {
        $this->mount();
        $this->dispatch('ToggleAttendanceModal');
    }

    public function loadEmployees()
    {
        $this->employees = User::employee()->active()->orderBy('name')->get();
    }

    public function getList()
    {
        $this->dayList = collect(UserAttendance::where('date', $this->date)->get()->toArray());
        foreach ($this->employees as $employee) {
            $this->attendance[$employee->id] = $this->dayList->where('employee_id', $employee->id)->count() > 0;
        }
    }

    public function calculateStats()
    {
        $totalEmployees = $this->employees->count();
        $presentCount = collect($this->attendance)->filter(fn ($value) => $value == true)->count();

        $percentage = $totalEmployees > 0 ? round(($presentCount / $totalEmployees) * 100, 1) : 0;

        $this->stats = [
            'total' => $totalEmployees,
            'present' => $presentCount,
            'absent' => $totalEmployees - $presentCount,
            'percentage' => $percentage,
        ];
    }

    public function toggleAttendance($employee_id)
    {
        $this->attendance[$employee_id] = ! $this->attendance[$employee_id];
        $this->calculateStats();
    }

    public function updatedSelectAll($value)
    {
        foreach ($this->employees as $employee) {
            $this->attendance[$employee->id] = $value;
        }
        $this->calculateStats();
    }

    public function save()
    {
        try {
            DB::beginTransaction();
            $action = new AttendanceAction();
            $response = $action->execute($this->date, $this->attendance);

            if (! $response['success']) {
                throw new \Exception($response['message']);
            }

            $this->dispatch('success', ['message' => $response['message']]);
            $this->getList();
            $this->calculateStats();

            $this->dispatch('ToggleAttendanceModal');
            $this->dispatch('RefreshAttendanceTable');
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.user.attendance.page');
    }
}
