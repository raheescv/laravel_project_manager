<?php

namespace App\Livewire\Report;

use App\Exports\DayBookReportExport;
use App\Jobs\Export\ExportSaleItemReportJob;
use App\Models\Models\Views\Ledger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class DayBookReport extends Component
{
    use WithPagination;

    public $search = '';

    public $account_id = '';

    public $branch_id = '';

    public $from_date;

    public $to_date;

    public $limit = 10;

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'ledgers.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->from_date = date('Y-m-d');
        $this->to_date = date('Y-m-d');
        $this->branch_id = session('branch_id');
    }

    public function export()
    {
        $count = Ledger::when($this->search, function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('description', 'like', "%{$value}%")
                    ->orWhere('reference_number', 'like', "%{$value}%")
                    ->orWhere('remarks', 'like', "%{$value}%");
            });
        })->when($this->from_date ?? '', function ($query, $value) {
            return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
        })->when($this->to_date ?? '', function ($query, $value) {
            return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
        })->when($this->branch_id ?? '', function ($query, $value) {
            return $query->where('branch_id', $value);
        })->when($this->account_id ?? '', function ($query, $value) {
            return $query->where('account_id', $value);
        })->count();

        $filter = [
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'account_id' => $this->account_id,
        ];

        if ($count > 2000) {
            ExportSaleItemReportJob::dispatch(Auth::user(), $filter);
            $this->dispatch('success', ['message' => 'You will get your file in your mailbox.']);
        } else {
            $exportFileName = 'DayBookReport-'.now()->timestamp.'.xlsx';

            return Excel::download(new DayBookReportExport($filter), $exportFileName);
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
        $this->resetPage();
    }

    public function render()
    {
        $data = Ledger::select('id', 'date', 'account_name', 'description', 'reference_number', 'journal_model', 'journal_model_id', 'remarks', 'journal_remarks', 'debit', 'credit', 'balance')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('description', 'like', "%{$value}%")
                        ->orWhere('reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_remarks', 'like', "%{$value}%")
                        ->orWhere('remarks', 'like', "%{$value}%");
                });
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('branch_id', $value);
            })
            ->when($this->account_id ?? '', function ($query, $value) {
                return $query->where('account_id', $value);
            });
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['debit'] = $totalRow->sum('debit');
        $total['credit'] = $totalRow->sum('credit');

        return view('livewire.report.day-book-report', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
