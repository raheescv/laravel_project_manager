<?php

namespace App\Livewire\Appointment;

use App\Actions\Appointment\Item\DeleteAction;
use App\Exports\SaleExport;
use App\Jobs\Export\ExportSaleJob;
use App\Models\Appointment;
use App\Models\AppointmentItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $filter = [];

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'appointment_items.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Sale-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $this->filter['from_date'] = date('Y-m-d');
        $this->filter['to_date'] = date('Y-m-d');
        $this->filter['branch_id'] = session('branch_id');
        $this->filter['customer_id'] = null;
        $this->filter['employee_id'] = null;
        $this->filter['service_id'] = null;
        $this->filter['created_by'] = null;
        $this->filter['status'] = 'pending';
    }

    public function delete()
    {
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
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshSaleTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($key, $value)
    {
        if (! in_array($key, ['SelectAll']) && ! preg_match('/^selected\..*/', $key)) {
            $this->resetPage();
        }
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value ? $this->getBaseQuery()->limit(2000)->pluck('appointment_items.id')->toArray() : [];
    }

    public function export()
    {
        $count = $this->getBaseQuery()->count();
        if ($count > 2000) {
            ExportSaleJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'Sale_'.now()->timestamp.'.xlsx';

            return Excel::download(new SaleExport(), $exportFileName);
        }
    }

    public function view($id)
    {
        $this->dispatch('View-Appointment-Page-Component', $id);
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

    protected function getBaseQuery()
    {

        return AppointmentItem::filter($this->filter)
            ->join('appointments', 'appointments.id', '=', 'appointment_items.appointment_id')
            ->join('accounts', 'accounts.id', '=', 'appointments.account_id')
            ->join('products', 'products.id', '=', 'appointment_items.service_id')
            ->join('users', 'users.id', '=', 'appointment_items.employee_id')
            ->join('users as creator', 'creator.id', '=', 'appointment_items.created_by');
    }

    protected function getCounts()
    {
        $filter = $this->filter;
        unset($filter['status']);
        $query = Appointment::filter($filter);

        $total = (clone $query)->count();
        $completed = (clone $query)->completed()->count();
        $pending = (clone $query)->pending()->count();
        $cancelled = (clone $query)->cancelled()->count();
        $noResponse = (clone $query)->noResponse()->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'cancelled' => $cancelled,
            'no_response' => $noResponse,
        ];
    }

    public function render()
    {
        $query = $this->getBaseQuery();

        $data = $query->orderBy($this->sortField, $this->sortDirection)
            ->select(
                'appointment_items.id',
                'appointment_id',
                'date',
                'start_time',
                'end_time',
                'accounts.name as customer_name',
                'products.name as service_name',
                'users.name as employee_name',
                'creator.name as creator_name',
            )
            ->paginate($this->limit);
        $counts = $this->getCounts();

        return view('livewire.appointment.table', [
            'data' => $data,
            'counts' => $counts,
        ]);
    }
}
