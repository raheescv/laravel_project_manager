<?php

namespace App\Livewire\Issue;

use App\Actions\Issue\DeleteAction;
use App\Actions\Issue\GetListAction;
use App\Models\Issue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public string $type = '';

    public string $search = '';

    public string $from_date = '';

    public string $to_date = '';

    public string $account_id = '';

    public int $limit = 10;

    public array $selected = [];

    public bool $selectAll = false;

    public string $sortField = 'id';

    public string $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function delete(): void
    {
        try {
            DB::beginTransaction();
            if (empty($this->selected)) {
                $this->dispatch('error', ['message' => 'Please select at least one item to delete.']);

                return;
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute((int) $id, Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message']);
                }
            }
            $count = count($this->selected);
            $this->dispatch('success', ['message' => 'Successfully deleted '.$count.' issue(s).']);
            DB::commit();
            $this->selected = [];
            $this->selectAll = false;
            if ($count > 10) {
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selected = Issue::orderByDesc('id')->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function sortBy(string $field): void
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
        $filter = [
            'search' => $this->search,
            'account_id' => $this->account_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'type' => $this->type,
        ];
        $data = (new GetListAction())->execute($filter, $this->limit, $this->sortField, $this->sortDirection);

        $collection = collect($data->items());
        $totals = [
            'no_of_items_out' => $collection->sum('no_of_items_out'),
            'no_of_items_in' => $collection->sum('no_of_items_in'),
            'balance' => $collection->sum(fn ($row) => $row->balance ?? 0),
        ];

        return view('livewire.issue.table', [
            'data' => $data,
            'totals' => $totals,
        ]);
    }
}
