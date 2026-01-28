<?php

namespace App\Livewire\Account;

use App\Actions\Account\DeleteAction;
use App\Exports\AccountExport;
use App\Jobs\Export\ExportAccountJob;
use App\Models\Account;
use App\Models\Configuration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $account_type = '';

    public $account_category_id = '';

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'accounts.id';

    public $sortDirection = 'desc';

    public $selectedAccountId = null;

    public $expandedTypes = [];

    public $expandedCategories = [];

    public $excludeCustomer = true;

    public $excludeVendor = true;

    public $visibleColumns = [
        'id' => true,
        'account_type' => true,
        'account_category' => true,
        'name' => true,
        'alias_name' => true,
        'description' => true,
        'model' => true,
    ];

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Account-Refresh-Component' => '$refresh',
    ];

    public function mount()
    {
        $config = Configuration::where('key', 'account_table_visible_columns')->value('value');
        $savedColumns = $config ? json_decode($config, true) : [];
        $defaultColumns = $this->getDefaultColumns();

        // Merge saved columns with defaults to ensure all columns are present
        $this->visibleColumns = array_merge($defaultColumns, $savedColumns);
    }

    protected function getDefaultColumns()
    {
        return [
            'id' => true,
            'account_type' => true,
            'account_category' => true,
            'name' => true,
            'alias_name' => true,
            'description' => true,
            'model' => true,
        ];
    }

    public function delete()
    {
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any item to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute($id);
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' items']);
            DB::commit();
            if (count($this->selected) > 10) {
                $this->resetPage();
            }
            $this->selected = [];

            $this->selectAll = false;
            $this->dispatch('RefreshAccountTable');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', ['message' => $e->getMessage()]);
        }
    }

    public function export()
    {
        $count = Account::count();
        if ($count > 2000) {
            ExportAccountJob::dispatch(Auth::user());
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'account_'.now()->timestamp.'.xlsx';

            return Excel::download(new AccountExport(), $exportFileName);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = Account::latest()->limit(2000)->pluck('id')->toArray();
        } else {
            $this->selected = [];
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
    }

    public function updated($key, $value)
    {
        if ($key !== 'selectedAccountId' && ! str_starts_with($key, 'visibleColumns.')) {
            $this->selectedAccountId = null;
        }

        // Save column visibility to Configuration when it changes
        if (str_starts_with($key, 'visibleColumns.')) {
            Configuration::updateOrCreate(
                ['key' => 'account_table_visible_columns'],
                ['value' => json_encode($this->visibleColumns)]
            );
        } else {
            $this->resetPage();
        }
    }

    public function toggleColumn($column)
    {
        if (isset($this->visibleColumns[$column])) {
            $this->visibleColumns[$column] = ! $this->visibleColumns[$column];
            // Save to Configuration
            Configuration::updateOrCreate(
                ['key' => 'account_table_visible_columns'],
                ['value' => json_encode($this->visibleColumns)]
            );
        }
    }

    public function resetColumnVisibility()
    {
        $this->visibleColumns = $this->getDefaultColumns();
        Configuration::updateOrCreate(
            ['key' => 'account_table_visible_columns'],
            ['value' => json_encode($this->visibleColumns)]
        );
    }

    public function toggleType($typeKey)
    {
        if (in_array($typeKey, $this->expandedTypes)) {
            $this->expandedTypes = array_values(array_diff($this->expandedTypes, [$typeKey]));
        } else {
            $this->expandedTypes[] = $typeKey;
        }
    }

    public function toggleCategory($categoryId)
    {
        $key = (string) $categoryId;
        if (in_array($key, $this->expandedCategories)) {
            $this->expandedCategories = array_values(array_diff($this->expandedCategories, [$key]));
        } else {
            $this->expandedCategories[] = $key;
        }
    }

    public function isTypeExpanded($typeKey)
    {
        return in_array($typeKey, $this->expandedTypes);
    }

    public function isCategoryExpanded($categoryId)
    {
        return in_array((string) $categoryId, $this->expandedCategories);
    }

    public function filterByType($type)
    {
        $this->account_type = $this->account_type === $type ? '' : $type;
        $this->account_category_id = '';
        $this->selectedAccountId = null;

        // Auto-expand when filtering
        if ($this->account_type && ! in_array($type, $this->expandedTypes)) {
            $this->expandedTypes[] = $type;
        }

        $this->resetPage();
    }

    public function filterByCategory($categoryId)
    {
        $this->account_category_id = $this->account_category_id == $categoryId ? '' : $categoryId;
        $this->selectedAccountId = null;

        // Auto-expand when filtering
        if ($this->account_category_id) {
            $account = Account::where('account_category_id', $categoryId)->first();
            if ($account && ! in_array($account->account_type, $this->expandedTypes)) {
                $this->expandedTypes[] = $account->account_type;
            }
            $key = (string) $categoryId;
            if (! in_array($key, $this->expandedCategories)) {
                $this->expandedCategories[] = $key;
            }
        }

        $this->resetPage();
    }

    public function filterByAccount($accountId)
    {
        $account = Account::find($accountId);
        if ($account) {
            $this->account_type = $account->account_type;
            $this->account_category_id = $account->account_category_id;
            $this->selectedAccountId = $accountId;

            // Auto-expand when filtering
            if (! in_array($account->account_type, $this->expandedTypes)) {
                $this->expandedTypes[] = $account->account_type;
            }
            if ($account->account_category_id) {
                $key = (string) $account->account_category_id;
                if (! in_array($key, $this->expandedCategories)) {
                    $this->expandedCategories[] = $key;
                }
            }
        } else {
            $this->selectedAccountId = null;
        }
        $this->resetPage();
    }

    public function getTreeData()
    {
        $accounts = Account::with('accountCategory:id,name')
            ->orderBy('account_type')
            ->orderBy('name')
            ->get();

        $tree = [];
        $accountTypes = accountTypes();

        foreach ($accountTypes as $typeKey => $typeLabel) {
            $typeAccounts = $accounts->where('account_type', $typeKey);

            if ($typeAccounts->isEmpty()) {
                continue;
            }

            $categories = [];
            $uncategorized = [];

            foreach ($typeAccounts as $account) {
                $categoryName = $account->accountCategory?->name ?? 'Uncategorized';
                $categoryId = $account->account_category_id ?? 0;

                if ($categoryId) {
                    if (! isset($categories[$categoryId])) {
                        $categories[$categoryId] = [
                            'id' => $categoryId,
                            'name' => $categoryName,
                            'accounts' => [],
                        ];
                    }
                    $categories[$categoryId]['accounts'][] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'alias_name' => $account->alias_name,
                    ];
                } else {
                    $uncategorized[] = [
                        'id' => $account->id,
                        'name' => $account->name,
                        'alias_name' => $account->alias_name,
                    ];
                }
            }

            if (! empty($categories) || ! empty($uncategorized)) {
                $tree[$typeKey] = [
                    'label' => $typeLabel,
                    'categories' => $categories,
                    'uncategorized' => $uncategorized,
                ];
            }
        }

        return $tree;
    }

    public function render()
    {
        $data = Account::with('accountCategory:id,name')->orderBy($this->sortField, $this->sortDirection)
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('accounts.alias_name', 'like', "%{$value}%")
                        ->orWhere('accounts.mobile', 'like', "%{$value}%")
                        ->orWhere('accounts.email', 'like', "%{$value}%")
                        ->orWhere('accounts.model', 'like', "%{$value}%");
                });
            })
            ->when($this->account_type ?? '', function ($query, $value) {
                return $query->where('account_type', $value);
            })
            ->when($this->account_category_id ?? '', function ($query, $value) {
                return $query->where('account_category_id', $value);
            })
            ->when($this->selectedAccountId ?? '', function ($query, $value) {
                return $query->where('accounts.id', $value);
            })
            ->when($this->model ?? '', function ($query, $value) {
                return $query->where('model', $value);
            })
            ->when($this->excludeCustomer, function ($query) {
                return $query->where(function ($q) {
                    $q->where('model', '!=', 'Customer')
                        ->orWhereNull('model');
                });
            })
            ->when($this->excludeVendor, function ($query) {
                return $query->where(function ($q) {
                    $q->where('model', '!=', 'Vendor')
                        ->orWhereNull('model');
                });
            })
            ->latest()
            ->paginate($this->limit);

        $treeData = $this->getTreeData();

        $visibleColumnNames = [
            'id' => '#',
            'account_type' => 'Account Type',
            'account_category' => 'Account Category',
            'name' => 'Name',
            'alias_name' => 'Alias Name',
            'description' => 'Description',
            'model' => 'Model',
        ];

        return view('livewire.account.table', [
            'data' => $data,
            'visibleColumnNames' => $visibleColumnNames,
            'treeData' => $treeData,
        ]);
    }
}
