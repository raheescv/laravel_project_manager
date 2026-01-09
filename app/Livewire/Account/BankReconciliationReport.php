<?php

namespace App\Livewire\Account;

use App\Actions\Account\BankReconciliation\UpdateDeliveredDateAction;
use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class BankReconciliationReport extends Component
{
    use WithPagination;

    // Filter constants
    private const FILTER_ALL = 'all';

    private const FILTER_DELIVERED = 'delivered';

    private const FILTER_PENDING = 'pending';

    // Default values
    private const DEFAULT_PER_PAGE = 25;

    private const DEFAULT_SORT_FIELD = 'journal_entries.date';

    private const DEFAULT_SORT_DIRECTION = 'desc';

    // Public properties
    public ?int $account_id = null;

    public string $from_date;

    public string $to_date;

    public string $delivered_date_filter = self::FILTER_PENDING;

    public int $perPage = self::DEFAULT_PER_PAGE;

    public array $selected = [];

    public bool $selectAll = false;

    public ?string $bulkDeliveredDate = null;

    public array $rowDates = [];

    public string $sortField = self::DEFAULT_SORT_FIELD;

    public string $sortDirection = self::DEFAULT_SORT_DIRECTION;

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updated(string $propertyName): void
    {
        $filterProperties = ['account_id', 'from_date', 'to_date', 'delivered_date_filter'];

        if (in_array($propertyName, $filterProperties)) {
            $this->resetFilters();
        }
        
        // Clean up rowDates when it's updated to prevent duplicates
        if (str_starts_with($propertyName, 'rowDates.')) {
            $this->normalizeRowDates();
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = self::DEFAULT_SORT_DIRECTION;
        }
        
        // Clean up rowDates to remove any stale entries after sorting
        $this->cleanupRowDates();
    }

    private function resetFilters(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        $currentPageIds = $this->items->pluck('id')->toArray();

        if ($value) {
            $this->selected = array_unique(array_merge($this->selected, $currentPageIds));
        } else {
            $this->selected = array_values(array_diff($this->selected, $currentPageIds));
        }
    }

    public function getSelectAllProperty(): bool
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return false;
        }

        $currentPageIds = $items->pluck('id')->toArray();
        $selectedOnPage = array_intersect($this->selected, $currentPageIds);

        return count($selectedOnPage) === count($currentPageIds) && count($currentPageIds) > 0;
    }

    protected function getBaseQuery(): Builder
    {
        return JournalEntry::query()
            ->with(['account', 'journal'])
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->leftJoin('account_categories', 'accounts.account_category_id', '=', 'account_categories.id')
            ->where($this->getBankAccountFilter())
            ->whereBetween('journal_entries.date', [$this->from_date, $this->to_date])
            ->when($this->account_id, fn ($q) => $q->where('journal_entries.account_id', $this->account_id))
            ->when($this->delivered_date_filter === self::FILTER_DELIVERED,
                fn ($q) => $q->whereNotNull('journal_entries.delivered_date'))
            ->when($this->delivered_date_filter === self::FILTER_PENDING,
                fn ($q) => $q->whereNull('journal_entries.delivered_date'))
            ->select('journal_entries.*', 'accounts.name as account_name', 'account_categories.name as category_name')
            ->orderBy($this->sortField, $this->sortDirection);
    }

    protected function getBankAccountFilter(): \Closure
    {
        return function (Builder $query) {
            $query->where('account_categories.name', 'Bank')
                ->orWhere(function (Builder $subQuery) {
                    $subQuery->where('accounts.name', 'like', '%bank%')
                        ->orWhere('accounts.name', 'like', '%card%')
                        ->orWhere('accounts.name', 'like', '%credit%');
                });
        };
    }

    public function getItemsProperty(): LengthAwarePaginator
    {
        $items = $this->getBaseQuery()->paginate($this->perPage);
        
        // Ensure rowDates only contains entries for current page items
        $this->normalizeRowDates();
        
        return $items;
    }

    public function getBankAccountsProperty()
    {
        return Account::query()
            ->leftJoin('account_categories', 'accounts.account_category_id', '=', 'account_categories.id')
            ->where($this->getBankAccountFilter())
            ->whereNull('accounts.deleted_at')
            ->select('accounts.id', 'accounts.name')
            ->orderBy('accounts.name')
            ->get();
    }

    public function updateDeliveredDate(): void
    {
        if (! $this->validateBulkUpdate()) {
            return;
        }

        $this->executeUpdate(
            $this->selected,
            $this->bulkDeliveredDate,
            function (): void {
                $this->selected = [];
                $this->selectAll = false;
                $this->bulkDeliveredDate = null;
            }
        );
    }

    public function updateRowDate(int $id): void
    {
        if (! isset($this->rowDates[$id]) || empty($this->rowDates[$id])) {
            $this->dispatch('error', ['message' => 'Please select a delivered date for this row.']);

            return;
        }
        $this->executeUpdate(
            [$id],
            $this->rowDates[$id],
            function () use ($id): void {
                unset($this->rowDates[$id]);
            },
            'Delivered date updated successfully.'
        );
    }

    public function clearRowDate(int $id): void
    {
        unset($this->rowDates[$id]);
    }

    private function cleanupRowDates(): void
    {
        // Get all current item IDs to ensure rowDates only contains valid entries
        $currentItemIds = $this->items->pluck('id')->toArray();
        
        // Remove any entries in rowDates that don't match current items
        $this->rowDates = array_intersect_key($this->rowDates, array_flip($currentItemIds));
        
        // Normalize the array to prevent duplicates
        $this->normalizeRowDates();
    }

    private function normalizeRowDates(): void
    {
        // Ensure rowDates only contains entries for current items
        $currentItemIds = $this->items->pluck('id')->toArray();
        
        // Remove entries that don't match current items
        $normalized = [];
        foreach ($this->rowDates as $id => $date) {
            // Only keep entries for current items and ensure ID is an integer
            if (in_array((int) $id, $currentItemIds, true)) {
                $normalized[(int) $id] = $date;
            }
        }
        
        $this->rowDates = $normalized;
        
        // Remove entries that match the current delivered_date (no need to track unchanged dates)
        foreach ($this->rowDates as $id => $date) {
            $item = $this->items->firstWhere('id', $id);
            if ($item && !empty($date) && $date === ($item->delivered_date ?? '')) {
                unset($this->rowDates[$id]);
            }
        }
    }

    public function updateMultipleRows(): void
    {
        $updates = $this->getValidRowDates();

        if (empty($updates)) {
            $this->dispatch('error', ['message' => 'Please set at least one valid date.']);

            return;
        }

        $this->executeMultipleUpdates($updates);
    }

    public function updateSingleDeliveredDate(int $id, string $date): void
    {
        if (empty($date)) {
            $this->dispatch('error', ['message' => 'Please select a delivered date.']);

            return;
        }

        $this->executeUpdate([$id], $date);
    }

    private function validateBulkUpdate(): bool
    {
        if (empty($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select at least one item to update.']);

            return false;
        }

        if (empty($this->bulkDeliveredDate)) {
            $this->dispatch('error', ['message' => 'Please select a delivered date.']);

            return false;
        }

        return true;
    }

    private function getValidRowDates(): array
    {
        $updates = [];
        foreach ($this->rowDates as $id => $date) {
            if (! empty($date)) {
                $updates[$id] = $date;
            }
        }

        return $updates;
    }

    private function executeUpdate(
        array $ids,
        string $date,
        ?\Closure $onSuccess = null,
        ?string $successMessage = null
    ): void {
        try {
            $action = new UpdateDeliveredDateAction();
            $result = $action->execute($ids, $date);

            if ($result['success']) {
                $message = $successMessage ?? $result['message'];
                $this->dispatch('success', ['message' => $message]);

                if ($onSuccess) {
                    $onSuccess();
                }
            } else {
                $this->dispatch('error', ['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    private function executeMultipleUpdates(array $updates): void
    {
        try {
            $action = new UpdateDeliveredDateAction();
            $result = $action->executeMultiple($updates);

            if ($result['success']) {
                $this->dispatch('success', ['message' => $result['message']]);
                $this->rowDates = [];
            } else {
                $this->dispatch('error', ['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function getSummaryProperty(): object
    {
        $baseQuery = $this->getBaseQuery();

        return (object) [
            'total_debit' => (clone $baseQuery)->sum('journal_entries.debit'),
            'total_credit' => (clone $baseQuery)->sum('journal_entries.credit'),
            'total_count' => (clone $baseQuery)->count(),
            'delivered_count' => (clone $baseQuery)->whereNotNull('journal_entries.delivered_date')->count(),
            'pending_count' => (clone $baseQuery)->whereNull('journal_entries.delivered_date')->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.account.bank-reconciliation-report', [
            'items' => $this->items,
            'bankAccounts' => $this->bankAccounts,
            'summary' => $this->summary,
        ]);
    }
}
