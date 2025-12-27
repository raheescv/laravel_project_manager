<?php

namespace App\Livewire\Account;

use App\Actions\Account\BankReconciliation\UpdateDeliveredDateAction;
use App\Models\Account;
use App\Models\JournalEntry;
use Livewire\Component;
use Livewire\WithPagination;

class BankReconciliationReport extends Component
{
    use WithPagination;

    public $account_id;

    public $from_date;

    public $to_date;

    public $delivered_date_filter = 'pending';

    public $perPage = 25;

    public $selected = [];

    public $selectAll = false;

    public $bulkDeliveredDate;

    public $rowDates = [];

    // Sorting properties
    public $sortBy = 'date';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['account_id', 'from_date', 'to_date', 'delivered_date_filter', 'sortBy', 'sortDirection'])) {
            $this->resetPage();
            if (! in_array($propertyName, ['sortBy', 'sortDirection'])) {
                $this->selected = [];
                $this->selectAll = false;
            }
        }
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        // Get current page items - use the items property which is already computed
        $items = $this->items;
        $currentPageIds = $items->pluck('id')->toArray();

        if ($value) {
            // Select all items on current page only
            $this->selected = array_unique(array_merge($this->selected, $currentPageIds));
        } else {
            // Deselect all items on current page
            $this->selected = array_values(array_diff($this->selected, $currentPageIds));
        }
    }

    public function getSelectAllProperty()
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return false;
        }

        $currentPageIds = $items->pluck('id')->toArray();
        $selectedOnPage = array_intersect($this->selected, $currentPageIds);

        return count($selectedOnPage) === count($currentPageIds) && count($currentPageIds) > 0;
    }

    protected function getBaseQuery()
    {
        return JournalEntry::query()
            ->with(['account', 'journal'])
            ->join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->leftJoin('account_categories', 'accounts.account_category_id', '=', 'account_categories.id')
            ->where($this->getBankAccountFilter())
            ->whereBetween('journal_entries.date', [$this->from_date, $this->to_date])
            ->when($this->account_id, fn($q) => $q->where('journal_entries.account_id', $this->account_id))
            ->when($this->delivered_date_filter === 'delivered', fn($q) => $q->whereNotNull('journal_entries.delivered_date'))
            ->when($this->delivered_date_filter === 'pending', fn($q) => $q->whereNull('journal_entries.delivered_date'))
            ->select('journal_entries.*', 'accounts.name as account_name', 'account_categories.name as category_name');
    }

    protected function getBankAccountFilter()
    {
        return function ($q) {
            $q->where('account_categories.name', 'Bank')
                ->orWhere(function ($subQ) {
                    $subQ->where('accounts.name', 'like', '%bank%')
                        ->orWhere('accounts.name', 'like', '%card%')
                        ->orWhere('accounts.name', 'like', '%credit%');
                });
        };
    }

    protected function getSortFieldMapping(): array
    {
        return [
            'date' => 'journal_entries.date',
            'account' => 'accounts.name',
            'description' => 'journal_entries.description',
            'reference' => 'journal_entries.reference_number',
            'debit' => 'journal_entries.debit',
            'credit' => 'journal_entries.credit',
            'delivered_date' => 'journal_entries.delivered_date',
        ];
    }

    public function getItemsProperty()
    {
        $query = $this->getBaseQuery();
        $sortMapping = $this->getSortFieldMapping();
        $sortField = $sortMapping[$this->sortBy] ?? 'journal_entries.date';

        $query->orderBy($sortField, $this->sortDirection);

        // Secondary sort for consistency
        if ($this->sortBy !== 'date') {
            $query->orderBy('journal_entries.date', 'desc');
        }
        $query->orderBy('journal_entries.id', 'desc');

        return $query->paginate($this->perPage);
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

    public function updateDeliveredDate()
    {
        if (empty($this->selected)) {
            $this->dispatch('error', ['message' => 'Please select at least one item to update.']);

            return;
        }

        if (empty($this->bulkDeliveredDate)) {
            $this->dispatch('error', ['message' => 'Please select a delivered date.']);

            return;
        }

        try {
            $action = new UpdateDeliveredDateAction();
            $result = $action->execute($this->selected, $this->bulkDeliveredDate);

            if ($result['success']) {
                $this->dispatch('success', ['message' => $result['message']]);
                $this->selected = [];
                $this->selectAll = false;
                $this->bulkDeliveredDate = null;
            } else {
                $this->dispatch('error', ['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function updateRowDate($id)
    {
        if (! isset($this->rowDates[$id]) || empty($this->rowDates[$id])) {
            $this->dispatch('error', ['message' => 'Please select a delivered date for this row.']);

            return;
        }

        try {
            $action = new UpdateDeliveredDateAction();
            $result = $action->execute([$id], $this->rowDates[$id]);

            if ($result['success']) {
                $this->dispatch('success', ['message' => 'Delivered date updated successfully.']);
                unset($this->rowDates[$id]);
            } else {
                $this->dispatch('error', ['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function clearRowDate($id)
    {
        unset($this->rowDates[$id]);
    }

    public function updateMultipleRows()
    {
        if (empty($this->rowDates)) {
            $this->dispatch('error', ['message' => 'Please set dates for at least one row.']);

            return;
        }

        $updates = [];
        foreach ($this->rowDates as $id => $date) {
            if (! empty($date)) {
                $updates[$id] = $date;
            }
        }

        if (empty($updates)) {
            $this->dispatch('error', ['message' => 'Please set at least one valid date.']);

            return;
        }

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

    public function updateSingleDeliveredDate($id, $date)
    {
        if (empty($date)) {
            $this->dispatch('error', ['message' => 'Please select a delivered date.']);

            return;
        }

        try {
            $action = new UpdateDeliveredDateAction();
            $result = $action->execute([$id], $date);

            if ($result['success']) {
                $this->dispatch('success', ['message' => $result['message']]);
            } else {
                $this->dispatch('error', ['message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.account.bank-reconciliation-report', [
            'items' => $this->items,
            'bankAccounts' => $this->bankAccounts,
        ]);
    }
}
