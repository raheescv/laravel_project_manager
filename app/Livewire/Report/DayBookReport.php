<?php

namespace App\Livewire\Report;

use App\Exports\DayBookReportExport;
use App\Jobs\Export\ExportSaleItemReportJob;
use App\Models\JournalEntry;
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

    public $sortField = 'journal_entries.id';

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
        $count = JournalEntry::when($this->search, function ($query, $value) {
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
        $data = JournalEntry::join('accounts', 'journal_entries.account_id', '=', 'accounts.id')
            ->select(
                'journal_entries.journal_id',
                'journal_entries.id',
                'journal_entries.date',
                'journal_entries.account_id',
                'accounts.name as account_name',
                'journal_entries.description',
                'journal_entries.reference_number',
                'journal_entries.model',
                'journal_entries.model_id',
                'journal_entries.journal_model',
                'journal_entries.journal_model_id',
                'journal_entries.remarks',
                'journal_entries.journal_remarks',
                'journal_entries.debit',
                'journal_entries.credit'
            )
            ->whereNull('journal_entries.deleted_at')
            ->when($this->search, function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('journal_entries.description', 'like', "%{$value}%")
                        ->orWhere('journal_entries.reference_number', 'like', "%{$value}%")
                        ->orWhere('journal_entries.journal_remarks', 'like', "%{$value}%")
                        ->orWhere('journal_entries.remarks', 'like', "%{$value}%");
                });
            })
            ->when($this->from_date ?? '', function ($query, $value) {
                return $query->where('journal_entries.date', '>=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->to_date ?? '', function ($query, $value) {
                return $query->where('journal_entries.date', '<=', date('Y-m-d', strtotime($value)));
            })
            ->when($this->branch_id ?? '', function ($query, $value) {
                return $query->where('journal_entries.branch_id', $value);
            })
            ->when($this->account_id ?? '', function ($query, $value) {
                return $query->where('journal_entries.account_id', $value);
            });
        $totalRow = clone $data;
        $data = $data->paginate($this->limit);

        $total['debit'] = $totalRow->sum('journal_entries.debit');
        $total['credit'] = $totalRow->sum('journal_entries.credit');

        return view('livewire.report.day-book-report', [
            'total' => $total,
            'data' => $data,
        ]);
    }
}
