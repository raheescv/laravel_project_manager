<?php

namespace App\Livewire\PurchaseRequest;

use App\Actions\PurchaseRequest\DeleteAction;
use App\Models\PurchaseRequest;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public ?string $search = null;

    public ?int $branch_id = null;

    // public ?int $created_by = null;
    // public ?int $decision_by = null;
    public ?string $status = null;

    public int $limit = 10;

    public array $selected = [];

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    #[Computed()]
    public function requests()
    {
        $query = PurchaseRequest::query()->with(['branch', 'tenant', 'creator', 'decisionMaker'])->withCount('products');

        $filters = [
            'search' => $this->search,
            'branch_id' => $this->branch_id,
            // 'created_by' => $this->created_by,
            // 'decision_by' => $this->decision_by,
            'status' => $this->status,
        ];

        $query->filter($filters);

        if (auth()->user()->can('purchase request.view own') && ! auth()->user()->can('purchase request.view any')) {
            $query->ownedBy(auth()->id());
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
        if (auth()->user()->cannot('purchase request.delete-any')) {
            $this->dispatch('error', [
                'message' => 'You do not have permission to delete purchase requests.',
            ]);
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
        if (in_array($name, ['search', 'branch_id', 'status'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        return view('livewire.purchase-request.table');
    }
}
