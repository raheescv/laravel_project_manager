<?php

namespace App\Livewire\Report\Customer;

use App\Exports\CustomerReminderCallbackExport;
use App\Traits\BuildsCustomerReminderQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class CustomerCallbackReminder extends Component
{
    use BuildsCustomerReminderQuery, WithPagination;

    public $customer_id;

    public $branch_id;

    public $from_date;

    public $to_date;

    public $product_id;

    public $category_id;

    public $nationality;

    public $reminder_cutoff_date;

    public $perPage = 20; // Optimized pagination

    public $totalCustomers = 0;

    public $loading = false; // Add loading state

    public $sortField = 'last_purchase_date'; // Default sort

    public $sortDirection = 'desc'; // Default sort direction

    public $searchTerm = ''; // Search functionality

    public $priorityFilter = 'all'; // Filter by priority (all, high, medium, low)

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'customerReminderCallbackFilterChanged' => 'filterChanged',
        'refreshData' => '$refresh',
    ];

    private const CACHE_KEY_CUSTOMER_DATA = 'reminder_callback_customer_data';

    private const CACHE_TTL = 300; // 5 minutes

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
        $this->reminder_cutoff_date = date('Y-m-d', strtotime('-30 days'));
    }

    public function filterChanged($payload)
    {
        $this->loading = true;

        try {
            $data = isset($payload['data']) ? $payload['data'] : $payload;

            $this->customer_id = ! empty($data['customer_id']) ? $data['customer_id'] : null;
            $this->from_date = ! empty($data['from_date']) ? $data['from_date'] : $this->from_date;
            $this->to_date = ! empty($data['to_date']) ? $data['to_date'] : $this->to_date;
            $this->branch_id = ! empty($data['branch_id']) ? $data['branch_id'] : null;
            $this->product_id = ! empty($data['product_id']) ? $data['product_id'] : null;
            $this->category_id = ! empty($data['category_id']) ? $data['category_id'] : null;
            $this->nationality = ! empty($data['nationality']) ? $data['nationality'] : null;
            $this->reminder_cutoff_date = ! empty($data['reminder_cutoff_date']) ? $data['reminder_cutoff_date'] : $this->reminder_cutoff_date;
            $this->searchTerm = $data['search'] ?? '';
            $this->priorityFilter = $data['priority'] ?? 'all';

            $this->resetPage();
            $this->clearCache();

        } catch (\Exception $e) {
            Log::error('Filter change failed: '.$e->getMessage());
            $this->dispatch('error', ['message' => 'Filter update failed. Please try again.']);
        } finally {
            $this->loading = false;
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
        $this->clearCache();
    }

    public function updatePerPage($value)
    {
        $this->perPage = (int) $value;
        $this->resetPage();
    }

    #[Computed]
    public function customerStats(): array
    {
        $cacheKey = 'customer_stats_'.md5(serialize($this->getFiltersArray()));

        return Cache::remember($cacheKey, self::CACHE_TTL / 2, function () {
            $filters = $this->getFiltersArray();
            $customers = $this->buildCustomerReminderListQuery($filters)->get();

            $customers->transform(function ($customer) {
                if ($customer->last_purchase_date) {
                    $customer->days_since_purchase = abs(now()->diffInDays($customer->last_purchase_date));
                } else {
                    $customer->days_since_purchase = 0;
                }

                return $customer;
            });

            $stats = $this->calculateStats($customers);

            return $stats;
        });
    }

    private function calculateStats(Collection $customers): array
    {
        $stats = [
            'total' => $customers->count(),
            'with_mobile' => $customers->whereNotNull('mobile')->where('mobile', '!=', '')->where('mobile', '!=', 'N/A')->count(),
            'with_email' => $customers->whereNotNull('email')->where('email', '!=', '')->where('email', '!=', 'N/A')->count(),
            'high_priority' => $customers->filter(fn ($c) => $c->days_since_purchase > 90)->count(),
            'medium_priority' => $customers->filter(fn ($c) => $c->days_since_purchase > 60 && $c->days_since_purchase <= 90)->count(),
            'low_priority' => $customers->filter(fn ($c) => $c->days_since_purchase > 30 && $c->days_since_purchase <= 60)->count(),
        ];

        return $stats;
    }

    public function exportData()
    {
        $this->loading = true;
        try {
            $filters = $this->getFiltersArray(); // Get all current filters

            // The actual data processing happens in the background with chunking.
            $this->dispatch('success', ['message' => 'Export process started! The file will download shortly.']);
            $exportFileName = 'CustomerCallbackReminder_'.now()->timestamp.'.xlsx';

            // Pass the filters array to the export class constructor
            return Excel::download(new CustomerReminderCallbackExport($filters), $exportFileName);

        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        } finally {
            $this->loading = false;
        }
    }

    public function refreshData()
    {
        $this->loading = true;

        try {
            $this->clearCache();
            $this->resetPage();
            $this->dispatch('success', ['message' => 'Data refreshed successfully!']);
        } catch (\Exception $e) {
            Log::error('Refresh failed: '.$e->getMessage());
            $this->dispatch('error', ['message' => 'Refresh failed. Please try again.']);
        } finally {
            $this->loading = false;
        }
    }

    private function clearCache(): void
    {
        Cache::forget('customer_stats_'.md5(serialize($this->getFiltersArray())));
    }

    private function getPriorityLabel(?int $days): string // This method seems unused now in this class, consider removing if not used by blade directly
    {
        if (! $days) {
            return 'Unknown';
        }

        return match (true) {
            $days > 90 => 'High Priority',
            $days > 60 => 'Medium Priority',
            $days > 30 => 'Low Priority',
            $days < 30 => 'Recent',
        };
    }

    private function getFilterHash(): string
    {
        return md5(serialize($this->getFiltersArray()));
    }

    public function getSortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return '<i class="fa fa-sort text-muted"></i>';
        }

        return $this->sortDirection === 'asc'
            ? '<i class="fa fa-sort-up"></i>'
            : '<i class="fa fa-sort-down"></i>';
    }

    private function getFiltersArray(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
            'nationality' => $this->nationality,
            'reminder_cutoff_date' => $this->reminder_cutoff_date,
            'search' => $this->searchTerm,
            'priority' => $this->priorityFilter,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
        ];
    }

    private function paginateCollection(Collection $collection): \Illuminate\Pagination\LengthAwarePaginator
    {
        $currentPage = $this->getPage();
        $perPage = $this->perPage;
        $total = $collection->count();

        $items = $collection->forPage($currentPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }

    public function render()
    {
        try {
            $filters = $this->getFiltersArray();
            $query = $this->buildCustomerReminderListQuery($filters);
            $paginatedCustomers = $query->paginate($this->perPage);

            collect($paginatedCustomers->items())->transform(function ($customer) {
                if ($customer->last_purchase_date) {
                    $customer->days_since_purchase = abs(now()->diffInDays($customer->last_purchase_date));
                } else {
                    // For display and filtering consistency, 0 is a reasonable default if it implies "no purchase history" or "very recent".
                    $customer->days_since_purchase = 0;
                }

                return $customer;
            });

            $this->totalCustomers = $paginatedCustomers->total();

            return view('livewire.report.customer.customer-callback-reminder', [
                'customers' => $paginatedCustomers,
                'stats' => $this->customerStats, // Use the computed property for stats for consistency
                'loading' => $this->loading,
            ]);

        } catch (\Exception $e) {
            Log::error('Render failed: '.$e->getMessage(), [
                'filters' => $this->getFiltersArray(), // Use the consistent getFiltersArray()
                'trace' => $e->getTraceAsString(),
            ]);

            return view('livewire.report.customer.customer-callback-reminder', [
                'customers' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage, $this->getPage()),
                'stats' => ['total' => 0, 'with_mobile' => 0, 'with_email' => 0, 'high_priority' => 0, 'medium_priority' => 0, 'low_priority' => 0],
                'loading' => false,
                'error' => 'An error occurred while loading the data. Please try again.',
            ]);
        }
    }
}
