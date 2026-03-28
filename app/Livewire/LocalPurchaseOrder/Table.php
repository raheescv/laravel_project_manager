<?php

namespace App\Livewire\LocalPurchaseOrder;

use App\Actions\LocalPurchaseOrder\DeleteAction;
use App\Models\Account;
use App\Models\LocalPurchaseOrder;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?int $branch_id = null;

    public ?int $vendor_id = null;

    public ?string $status = null;

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
    public function requests()
    {
        $query = LocalPurchaseOrder::query()->with(['branch', 'tenant', 'creator', 'decisionMaker', 'vendor'])->withCount('items');

        $filters = [
            'search' => $this->search,
            'branch_id' => $this->branch_id,
            'vendor_id' => $this->vendor_id,
            'status' => $this->status,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
        ];

        $query->filter($filters);

        if (Auth::user()->can('local purchase request.view own') && ! Auth::user()->can('local purchase request.view')) {
            $query->ownedBy(Auth::id());
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->limit);
    }

    #[Computed(true)]
    public function vendors()
    {
        return Account::select('id', 'name')->vendor()->latest()->get()->pluck('name', 'id');
    }

    #[Computed(true)]
    public function products()
    {
        return Product::select('id', 'name')->orderBy('name')->get()->pluck('name', 'id');
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
        if (Auth::user()->cannot('local purchase order.delete-own')) {
            $this->dispatch('error', [
                'message' => 'You do not have permission to delete purchase requests.',
            ]);

            return;
        }

        $response = (new DeleteAction())->execute($this->selected);

        if ($response['success']) {
            $this->dispatch('success', ['message' => $response['message']]);
            $this->selected = [];
        } else {
            $this->dispatch('error', ['message' => $response['message']]);
        }
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['search', 'branch_id', 'status', 'vendor_id', 'product_id', 'from_date', 'to_date'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        return view('livewire.local-purchase-order.table');
    }
}
