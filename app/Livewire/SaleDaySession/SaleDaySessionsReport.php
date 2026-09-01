<?php

namespace App\Livewire\SaleDaySession;

use App\Models\Branch;
use App\Models\SaleDaySession;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class SaleDaySessionsReport extends Component
{
    use WithPagination;

    public const PRESETS = [
        'today' => 'Today',
        '7d' => '7 days',
        '30d' => '30 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
    ];

    public const DEFAULT_PRESET = '30d';

    public const STATUSES = ['' => 'All', 'open' => 'Open', 'closed' => 'Closed'];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    protected const SORTABLE = ['id', 'opened_at', 'difference_amount'];

    public $branchId = '';

    public $dateFrom;

    public $dateTo;

    public $status = '';

    /** Which quick-range chip is active; 'custom' once a date is edited by hand. */
    public $preset = self::DEFAULT_PRESET;

    public $sortField = 'opened_at';

    public $sortDirection = 'desc';

    public $perPage = 25;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->applyPreset(self::DEFAULT_PRESET);
    }

    public function updated($property)
    {
        if (in_array($property, ['dateFrom', 'dateTo'], true)) {
            $this->preset = $this->detectPreset();
        }

        if (in_array($property, ['branchId', 'dateFrom', 'dateTo', 'status', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function setRange(string $preset): void
    {
        if (! array_key_exists($preset, self::PRESETS)) {
            return;
        }

        $this->applyPreset($preset);
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        if (! array_key_exists($status, self::STATUSES)) {
            return;
        }

        $this->status = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->branchId = '';
        $this->status = '';
        $this->applyPreset(self::DEFAULT_PRESET);
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $base = fn () => SaleDaySession::query()
            ->when($this->branchId, fn ($q) => $q->where('branch_id', $this->branchId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('opened_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('opened_at', '<=', $this->dateTo));

        $sortField = in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'opened_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, true) ? (int) $this->perPage : 25;

        $sessions = $base()
            ->with(['branch', 'opener', 'closer'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->withCount(['sales', 'tailoringOrders'])
            ->withSum('sales', 'paid')
            ->withSum('tailoringOrders', 'paid')
            ->orderBy($sortField, $sortDirection)
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // Summary ignores the status filter on purpose: it reports the open/closed split of the range.
        $stats = $base()
            ->select(['id', 'status', 'difference_amount'])
            ->withCount(['sales', 'tailoringOrders'])
            ->withSum('sales', 'paid')
            ->withSum('tailoringOrders', 'paid')
            ->get();

        $closed = $stats->where('status', 'closed');

        $summary = [
            'total_sessions' => $stats->count(),
            'open_sessions' => $stats->where('status', 'open')->count(),
            'closed_sessions' => $closed->count(),
            'total_sales' => (int) $stats->sum('sales_count'),
            'total_sales_amount' => (float) $stats->sum('sales_sum_paid'),
            'total_tailoring' => (int) $stats->sum('tailoring_orders_count'),
            'total_tailoring_amount' => (float) $stats->sum('tailoring_orders_sum_paid'),
            'total_difference' => (float) $closed->sum('difference_amount'),
        ];
        $summary['total_invoices'] = $summary['total_sales'] + $summary['total_tailoring'];
        $summary['total_collection_amount'] = $summary['total_sales_amount'] + $summary['total_tailoring_amount'];

        return view('livewire.sale-day-session.sale-day-sessions-report', [
            'sessions' => $sessions,
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'summary' => $summary,
            'presets' => self::PRESETS,
            'statuses' => self::STATUSES,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'hasActiveFilters' => filled($this->branchId) || filled($this->status) || $this->preset !== self::DEFAULT_PRESET,
        ]);
    }

    private function applyPreset(string $preset): void
    {
        [$this->dateFrom, $this->dateTo] = $this->rangeFor($preset);
        $this->preset = $preset;
    }

    private function detectPreset(): string
    {
        foreach (array_keys(self::PRESETS) as $preset) {
            if ($this->rangeFor($preset) === [$this->dateFrom, $this->dateTo]) {
                return $preset;
            }
        }

        return 'custom';
    }

    /** @return array{0: string, 1: string} [from, to] as Y-m-d */
    private function rangeFor(string $preset): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'today' => [$today->toDateString(), $today->toDateString()],
            '7d' => [$today->copy()->subDays(7)->toDateString(), $today->toDateString()],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            default => [$today->copy()->subDays(30)->toDateString(), $today->toDateString()],
        };
    }
}
