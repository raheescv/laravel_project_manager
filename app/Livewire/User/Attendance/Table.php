<?php

namespace App\Livewire\User\Attendance;

use App\Models\User;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public array $filter = [];

    public $presentCount = 0;

    public $limit = 20;

    public $sortField = 'name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Attendance-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->filter = [
            'search' => '',
            'employee_id' => '',
            'month' => date('Y-m'),
        ];
        $this->presentCount = UserAttendance::where('date', date('Y-m-d'))->count();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $startDate = Carbon::parse($this->filter['month'])->startOfMonth();
        $endDate = Carbon::parse($this->filter['month'])->endOfMonth();
        $data = User::query()
            ->select('users.*')
            ->employee()
            ->with(['attendances' => function ($query) use ($startDate, $endDate): void {
                $query->whereBetween('date', [$startDate, $endDate]);
            }])
            ->when($this->filter['search'], function ($query, $value): void {
                $query->where(function ($q) use ($value): void {
                    $q->where('users.name', 'like', '%'.$value.'%')
                        ->orWhere('users.email', 'like', '%'.$value.'%');
                });
            })
            ->when($this->filter['employee_id'], function ($query): void {
                $query->where('id', $this->filter['employee_id']);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        $employees = User::employee()->get();
        $daysInMonth = $endDate->day;

        return view('livewire.user.attendance.table', [
            'data' => $data,
            'employees' => $employees,
            'daysInMonth' => $daysInMonth,
            'currentMonth' => $startDate,
        ]);
    }
}
