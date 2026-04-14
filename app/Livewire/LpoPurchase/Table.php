<?php

namespace App\Livewire\LpoPurchase;

use App\Actions\LpoPurchase\DeleteAction;
use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?int $branch_id = null;

    public ?string $status = null;

    public ?int $vendor_id = null;

    public ?int $local_purchase_order_id = null;

    public ?int $created_by = null;

    public ?int $decision_by = null;

    public ?string $from_date = null;

    public ?string $to_date = null;

    public int $limit = 10;

    public array $selected = [];

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    #[Computed()]
    public function purchases()
    {
        $query = Purchase::query()
            ->lpoBased()
            ->with(['branch', 'tenant', 'createdUser', 'decisionMaker', 'localPurchaseOrder', 'account'])
            ->withCount('items');

        $filters = [
            'search' => $this->search,
            'branch_id' => $this->branch_id,
            'status' => $this->status,
            'vendor_id' => $this->vendor_id,
            'local_purchase_order_id' => $this->local_purchase_order_id,
            'created_by' => $this->created_by,
            'decision_by' => $this->decision_by,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];

        $query->lpoPurchaseFilter($filters);

        if (Auth::user()->can('lpo-purchase.view own') && ! Auth::user()->can('lpo-purchase.view')) {
            $query->where('created_by', Auth::id());
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->limit);
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

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (Auth::user()->cannot('lpo-purchase.delete')) {
                throw new Exception('You do not have permission to delete LPO Purchases', 1);
            }

            $response = (new DeleteAction())->execute($this->selected);
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            $this->selected = [];

            DB::commit();
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['search', 'branch_id', 'status', 'vendor_id', 'local_purchase_order_id', 'created_by', 'decision_by', 'from_date', 'to_date'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        return view('livewire.lpo-purchase.table');
    }
}
