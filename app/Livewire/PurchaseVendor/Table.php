<?php

namespace App\Livewire\PurchaseVendor;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 10;

    public $sortField = 'accounts.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

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
        $data = Account::vendor()
            ->where('account_type', 'liability')
            ->leftJoin('purchases', function ($join) {
                $join->on('accounts.id', '=', 'purchases.account_id')
                    ->whereNull('purchases.deleted_at');
            })
            ->select(
                'accounts.id',
                'accounts.name',
                'accounts.mobile',
                'accounts.place',
                DB::raw('COALESCE(SUM(purchases.grand_total), 0) as total_amount'),
                DB::raw('COALESCE(SUM(purchases.paid), 0) as total_paid'),
                DB::raw('COALESCE(SUM(purchases.balance), 0) as total_balance')
            )
            ->groupBy('accounts.id', 'accounts.name', 'accounts.mobile', 'accounts.place')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('accounts.mobile', 'like', "%{$value}%")
                        ->orWhere('accounts.place', 'like', "%{$value}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->limit);

        return view('livewire.purchase-vendor.table', [
            'data' => $data,
        ]);
    }
}
