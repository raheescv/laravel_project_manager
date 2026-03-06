<?php

namespace App\Livewire\Report;

use App\Models\IssueItem;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class IssueAgingReport extends Component
{
    use WithPagination;

    public $from_date;

    public $to_date;

    public $product_id = '';

    public $search = '';

    public $limit = 25;

    public $sortField = 'latest_issue_date';

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

    protected function baseAgingQuery()
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        $asOf = $this->to_date ? Carbon::parse($this->to_date) : Carbon::today();

        return IssueItem::query()
            ->join('issues', 'issues.id', '=', 'issue_items.issue_id')
            ->when($tenantId, fn ($q) => $q->where('issues.tenant_id', $tenantId))
            ->where('issues.date', '<=', $asOf->toDateString())
            ->when($this->product_id, fn ($q) => $q->where('issue_items.product_id', $this->product_id))
            ->when($this->search, function ($q) {
                $v = trim($this->search);
                $q->where(function ($q2) use ($v) {
                    $q2->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%"))
                        ->orWhereHas('issue.account', fn ($a) => $a->where('name', 'like', "%{$v}%"));
                });
            });
    }

    public function render()
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        $asOf = $this->to_date ? Carbon::parse($this->to_date) : Carbon::today();
        $d30 = $asOf->copy()->subDays(30)->toDateString();
        $d60 = $asOf->copy()->subDays(60)->toDateString();
        $d90 = $asOf->copy()->subDays(90)->toDateString();

        $query = IssueItem::query()
            ->join('issues', 'issues.id', '=', 'issue_items.issue_id')
            ->join('accounts', 'accounts.id', '=', 'issues.account_id')
            ->join('products', 'products.id', '=', 'issue_items.product_id')
            ->select([
                'issues.account_id',
                'issue_items.product_id',
                'accounts.name as account_name',
                'products.name as product_name',
                'products.code as product_code',
                DB::raw('MAX(issues.date) as latest_issue_date'),
                DB::raw('SUM(issue_items.quantity_out) as quantity_out'),
                DB::raw('SUM(issue_items.quantity_in) as quantity_in'),
                DB::raw('SUM(issue_items.quantity_out - issue_items.quantity_in) as net_quantity'),
                DB::raw("SUM(CASE WHEN issues.date >= '{$d30}' THEN (issue_items.quantity_out - issue_items.quantity_in) ELSE 0 END) as aging_0_30"),
                DB::raw("SUM(CASE WHEN issues.date >= '{$d60}' AND issues.date < '{$d30}' THEN (issue_items.quantity_out - issue_items.quantity_in) ELSE 0 END) as aging_31_60"),
                DB::raw("SUM(CASE WHEN issues.date >= '{$d90}' AND issues.date < '{$d60}' THEN (issue_items.quantity_out - issue_items.quantity_in) ELSE 0 END) as aging_61_90"),
                DB::raw("SUM(CASE WHEN issues.date < '{$d90}' THEN (issue_items.quantity_out - issue_items.quantity_in) ELSE 0 END) as aging_90_plus"),
            ])
            ->when($tenantId, fn ($q) => $q->where('issues.tenant_id', $tenantId))
            ->when($this->from_date, fn ($q) => $q->where('issues.date', '>=', $this->from_date))
            ->when($this->to_date, fn ($q) => $q->where('issues.date', '<=', $this->to_date))
            ->when($this->product_id, fn ($q) => $q->where('issue_items.product_id', $this->product_id))
            ->when($this->search, function ($q) {
                $v = trim($this->search);
                $q->where(function ($q2) use ($v) {
                    $q2->where('products.name', 'like', "%{$v}%")
                        ->orWhere('products.code', 'like', "%{$v}%")
                        ->orWhere('accounts.name', 'like', "%{$v}%");
                });
            })
            ->groupBy('issues.account_id', 'issue_items.product_id', 'accounts.name', 'products.name', 'products.code')
            ->havingRaw('SUM(issue_items.quantity_out - issue_items.quantity_in) <> 0')
            ->orderBy($this->sortField, $this->sortDirection);

        $totalQuery = clone $query;
        $data = $query->paginate($this->limit);

        $total = [
            'quantity_out' => (clone $totalQuery)->get()->sum('quantity_out'),
            'quantity_in' => (clone $totalQuery)->get()->sum('quantity_in'),
            'net_quantity' => (clone $totalQuery)->get()->sum('net_quantity'),
        ];

        // Aging buckets: 0–30, 31–60, 61–90, 90+ days (as of to_date)
        $base = $this->baseAgingQuery();
        $aging = [
            '0_30' => [
                'quantity_out' => (clone $base)->where('issues.date', '>=', $asOf->copy()->subDays(30)->toDateString())->sum('issue_items.quantity_out'),
                'quantity_in' => (clone $base)->where('issues.date', '>=', $asOf->copy()->subDays(30)->toDateString())->sum('issue_items.quantity_in'),
            ],
            '31_60' => [
                'quantity_out' => (clone $base)->where('issues.date', '>=', $asOf->copy()->subDays(60)->toDateString())->where('issues.date', '<', $asOf->copy()->subDays(30)->toDateString())->sum('issue_items.quantity_out'),
                'quantity_in' => (clone $base)->where('issues.date', '>=', $asOf->copy()->subDays(60)->toDateString())->where('issues.date', '<', $asOf->copy()->subDays(30)->toDateString())->sum('issue_items.quantity_in'),
            ],
            '61_90' => [
                'quantity_out' => (clone $base)->where('issues.date', '>=', $asOf->copy()->subDays(90)->toDateString())->where('issues.date', '<', $asOf->copy()->subDays(60)->toDateString())->sum('issue_items.quantity_out'),
                'quantity_in' => (clone $base)->where('issues.date', '>=', $asOf->copy()->subDays(90)->toDateString())->where('issues.date', '<', $asOf->copy()->subDays(60)->toDateString())->sum('issue_items.quantity_in'),
            ],
            '90_plus' => [
                'quantity_out' => (clone $base)->where('issues.date', '<', $asOf->copy()->subDays(90)->toDateString())->sum('issue_items.quantity_out'),
                'quantity_in' => (clone $base)->where('issues.date', '<', $asOf->copy()->subDays(90)->toDateString())->sum('issue_items.quantity_in'),
            ],
        ];

        foreach ($aging as $key => $bucket) {
            $aging[$key]['net_quantity'] = $bucket['quantity_out'] - $bucket['quantity_in'];
        }

        return view('livewire.report.issue-aging-report', [
            'data' => $data,
            'total' => $total,
            'aging' => $aging,
        ]);
    }
}
