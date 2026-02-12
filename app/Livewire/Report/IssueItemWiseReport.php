<?php

namespace App\Livewire\Report;

use App\Models\IssueItem;
use App\Services\TenantService;
use Livewire\Component;
use Livewire\WithPagination;

class IssueItemWiseReport extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $product_id = '';

    public $search = '';

    public $limit = 25;

    public $sortField = 'issues.date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
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

    public function updated($key, $value)
    {
        $this->resetPage();
    }

    public function render()
    {

        $query = IssueItem::query()
            ->with(['issue:id,account_id,date', 'issue.account:id,name', 'product:id,name,code'])
            ->join('issues', 'issues.id', '=', 'issue_items.issue_id')
            ->select('issue_items.*', 'issues.account_id', 'issues.date as issue_date')
            ->when($this->from_date, fn ($q) => $q->where('issues.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->where('issues.date', '<=', $this->to_date))
            ->when($this->product_id, fn ($q) => $q->where('issue_items.product_id', $this->product_id))
            ->when($this->search, function ($q) {
                $v = trim($this->search);
                $q->where(function ($q2) use ($v) {
                    $q2->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%"))
                        ->orWhereHas('issue.account', fn ($a) => $a->where('name', 'like', "%{$v}%"));
                });
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return view('livewire.report.issue-item-wise-report', [
            'data' => $query->paginate($this->limit),
        ]);
    }
}
