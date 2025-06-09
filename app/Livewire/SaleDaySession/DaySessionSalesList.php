<?php

namespace App\Livewire\SaleDaySession;

use App\Models\Sale;
use App\Models\SaleDaySession;
use Livewire\Component;
use Livewire\WithPagination;

class DaySessionSalesList extends Component
{
    use WithPagination;

    public $sessionId;

    public $session;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['refreshSalesList' => '$refresh'];

    public function mount($sessionId)
    {
        $this->sessionId = $sessionId;
        $this->loadSession();
    }

    public function loadSession()
    {
        $this->session = SaleDaySession::with(['branch', 'opener', 'closer'])->find($this->sessionId);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $sales = Sale::where('sale_day_session_id', $this->sessionId)
            ->when($this->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('invoice_no', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhere('customer_mobile', 'like', '%'.$search.'%')
                        ->orWhere('id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $totals = [
            'gross_amount' => Sale::where('sale_day_session_id', $this->sessionId)->sum('gross_amount'),
            'item_discount' => Sale::where('sale_day_session_id', $this->sessionId)->sum('item_discount'),
            'tax_amount' => Sale::where('sale_day_session_id', $this->sessionId)->sum('tax_amount'),
            'paid' => Sale::where('sale_day_session_id', $this->sessionId)->sum('paid'),
        ];

        return view('livewire.sale-day-session.day-session-sales-list', [
            'sales' => $sales,
            'totals' => $totals,
        ]);
    }
}
