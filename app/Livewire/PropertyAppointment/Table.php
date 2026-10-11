<?php

namespace App\Livewire\PropertyAppointment;

use App\Actions\PropertyAppointment\CancelAction;
use App\Actions\PropertyAppointment\DeleteAction;
use App\Actions\PropertyAppointment\GetAction;
use App\Exports\PropertyAppointmentExport;
use App\Models\PropertyAppointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $employee_id = '';

    public $branch_id = '';

    public $from_date = '';

    public $to_date = '';

    public $limit = 50;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'property_appointments.scheduled_at';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'PropertyAppointment-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->branch_id = session('branch_id');
        $this->from_date = date('Y-m-d');
        $this->to_date = now()->addDays(30)->format('Y-m-d');
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value ? $this->rows()->pluck('id')->map(fn ($id) => (string) $id)->toArray() : [];
    }

    public function updating($field)
    {
        if (in_array($field, ['search', 'status', 'employee_id', 'branch_id', 'from_date', 'to_date', 'limit'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    public function cancel($id)
    {
        abort_unless(Auth::user()?->can('property appointment.edit'), 403);
        try {
            DB::beginTransaction();
            $response = (new CancelAction())->execute($id, Auth::id(), 'Cancelled from the appointments list');
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

    public function delete()
    {
        abort_unless(Auth::user()?->can('property appointment.delete'), 403);
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id, Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            DB::commit();
            $this->selected = [];
            $this->selectAll = false;
            $this->dispatch('success', ['message' => 'Successfully deleted']);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function exportExcel()
    {
        abort_unless(Auth::user()?->can('property appointment.export'), 403);

        if (! $this->rows()->exists()) {
            $this->dispatch('error', ['message' => 'No data to export.']);

            return;
        }

        return Excel::download(new PropertyAppointmentExport($this->filters()), 'appointments_'.now()->timestamp.'.xlsx');
    }

    /** @return array<string, mixed> */
    private function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'employee_id' => $this->employee_id,
            'branch_id' => $this->branch_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
        ];
    }

    private function rows()
    {
        return (new GetAction())->execute($this->filters())['list'];
    }

    public function render()
    {
        $base = PropertyAppointment::query()
            ->when($this->branch_id, fn ($query, $value) => $query->where('branch_id', $value));

        return view('livewire.property-appointment.table', [
            'appointments' => $this->rows()->paginate($this->limit),
            'pdfUrl' => route('property::sale::appointment_schedule::pdf', array_filter($this->filters())),
            'stats' => [
                'upcoming' => (clone $base)->upcoming()->count(),
                'awaiting' => (clone $base)->where('status', 'awaiting')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'no_show' => (clone $base)->where('status', 'no_show')->count(),
            ],
        ]);
    }
}
