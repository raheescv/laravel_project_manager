<?php

namespace App\Livewire\Tenant;

use App\Models\Configuration;
use App\Models\Tenant;
use App\Models\TenantPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tenant Control → upcoming AMC renewals: every tenant whose renewal date is
 * overdue or falls inside the chosen window, soonest first.
 */
class AmcReminder extends Component
{
    use WithPagination;

    /** Days ahead to look: 7 | 30 | 60 | 90 | overdue | all. Overdue renewals are always included. */
    public string $window = '30';

    public string $search = '';

    public bool $includeInactive = false;

    public int $limit = 25;

    public string $sortField = 'renews_on';

    public string $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    /** @var array<int|string, array{0: string, 1: string}> */
    public const WINDOWS = [
        '7' => ['fa-bolt', 'Next 7 days'],
        '30' => ['fa-calendar', 'Next 30 days'],
        '60' => ['fa-calendar-o', 'Next 60 days'],
        '90' => ['fa-calendar-plus-o', 'Next 90 days'],
        'overdue' => ['fa-exclamation-circle', 'Overdue only'],
        'all' => ['fa-th-large', 'Every renewal'],
    ];

    /** @var list<string> */
    public const SORTABLE = ['renews_on', 'name', 'amc_amount', 'payments_max_paid_on'];

    public function mount(): void
    {
        abort_unless(Auth::user()?->is_super_admin, 403, 'Unauthorized access. Only super admin users can access this page.');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('window', 'search', 'includeInactive', 'sortField', 'sortDirection');
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    /**
     * Tenants with a renewal date, filtered by status and search but not by window.
     */
    private function scheduledQuery(): Builder
    {
        return Tenant::query()
            ->whereNotNull('renews_on')
            ->when(! $this->includeInactive, fn ($query) => $query->where('is_active', true))
            ->when($this->search, function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('subdomain', 'like', "%{$value}%");
                });
            });
    }

    private function windowQuery(): Builder
    {
        $window = array_key_exists($this->window, self::WINDOWS) ? $this->window : '30';

        return $this->scheduledQuery()->when($window !== 'all', fn ($query) => $query->whereDate(
            'renews_on',
            $window === 'overdue' ? '<' : '<=',
            $window === 'overdue' ? today() : today()->addDays((int) $window),
        ));
    }

    public function render()
    {
        $sortField = in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'renews_on';

        $data = $this->windowQuery()
            ->withMax('payments', 'paid_on')
            ->when($sortField === 'amc_amount', fn ($query) => $query->orderByRaw('amc_amount IS NULL'))
            ->orderBy($sortField, $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->orderBy('name')
            ->paginate($this->limit);

        $today = today()->toDateString();
        $weekEnd = today()->addDays(7)->toDateString();
        $totals = $this->scheduledQuery()
            ->selectRaw('COUNT(*) as scheduled_count')
            ->selectRaw('SUM(renews_on < ?) as overdue_count', [$today])
            ->selectRaw('SUM(CASE WHEN renews_on < ? THEN COALESCE(amc_amount, 0) ELSE 0 END) as overdue_amount', [$today])
            ->selectRaw('SUM(renews_on >= ? AND renews_on <= ?) as week_count', [$today, $weekEnd])
            ->selectRaw('SUM(CASE WHEN renews_on >= ? AND renews_on <= ? THEN COALESCE(amc_amount, 0) ELSE 0 END) as week_amount', [$today, $weekEnd])
            ->toBase()
            ->first();
        $windowTotals = $this->windowQuery()->selectRaw('COUNT(*) as total, COALESCE(SUM(amc_amount), 0) as amount')->toBase()->first();

        $contacts = Configuration::withoutGlobalScopes()
            ->whereIn('tenant_id', $data->pluck('id'))
            ->whereIn('key', ['mobile', 'email'])
            ->get(['tenant_id', 'key', 'value'])
            ->groupBy('tenant_id')
            ->map(fn ($rows) => $rows->pluck('value', 'key'));

        return view('livewire.tenant.amc-reminder', [
            'data' => $data,
            'contacts' => $contacts,
            'windows' => self::WINDOWS,
            'summary' => [
                'window' => [(int) $windowTotals->total, (float) $windowTotals->amount],
                'overdue' => [(int) $totals->overdue_count, (float) $totals->overdue_amount],
                'week' => [(int) $totals->week_count, (float) $totals->week_amount],
                'scheduled' => (int) $totals->scheduled_count,
                'collected' => (float) TenantPayment::where('type', 'amc')->whereBetween('paid_on', [today()->startOfMonth(), today()])->sum('amount'),
                'unscheduled' => Tenant::whereNull('renews_on')->where('is_active', true)->count(),
            ],
        ]);
    }
}
