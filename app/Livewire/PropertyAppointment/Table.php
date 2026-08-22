<?php

namespace App\Livewire\PropertyAppointment;

use App\Actions\PropertyAppointment\CancelAction;
use App\Actions\PropertyAppointment\DeleteAction;
use App\Models\PropertyAppointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $salesman_id = '';

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
        if (in_array($field, ['search', 'status', 'salesman_id', 'branch_id', 'from_date', 'to_date', 'limit'], true)) {
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
        abort_unless(auth()->user()?->can('property appointment.edit'), 403);
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
        abort_unless(auth()->user()?->can('property appointment.delete'), 403);
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

    private function rows()
    {
        return PropertyAppointment::query()
            ->with(['customer:id,name,mobile', 'salesman:id,name', 'rentOut:id,property_id', 'rentOut.property:id,number'])
            ->when($this->search, function ($query, $value) {
                // The reference in the list is derived (VW-<year>-<padded id>), so read
                // the id out of whatever was typed: "14", "0014" and "VW-2026-0014" all
                // point at appointment 14. A padded string can never LIKE-match the id.
                $id = (int) Str::afterLast($value, '-');

                $query->where(function ($query) use ($value, $id) {
                    $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$value}%"))
                        ->orWhereHas('rentOut.property', fn ($q) => $q->where('number', 'like', "%{$value}%"))
                        ->when($id, fn ($q) => $q->orWhere('property_appointments.id', $id));
                });
            })
            ->when($this->status, fn ($query, $value) => $query->where('property_appointments.status', $value))
            ->when($this->salesman_id, fn ($query, $value) => $query->where('property_appointments.salesman_id', $value))
            ->when($this->branch_id, fn ($query, $value) => $query->where('property_appointments.branch_id', $value))
            ->when($this->from_date, fn ($query, $value) => $query->whereDate('property_appointments.scheduled_at', '>=', $value))
            ->when($this->to_date, fn ($query, $value) => $query->whereDate('property_appointments.scheduled_at', '<=', $value))
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $base = PropertyAppointment::query()
            ->when($this->branch_id, fn ($query, $value) => $query->where('branch_id', $value));

        return view('livewire.property-appointment.table', [
            'appointments' => $this->rows()->paginate($this->limit),
            'stats' => [
                'upcoming' => (clone $base)->upcoming()->count(),
                'awaiting' => (clone $base)->where('status', 'awaiting')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'no_show' => (clone $base)->where('status', 'no_show')->count(),
            ],
        ]);
    }
}
