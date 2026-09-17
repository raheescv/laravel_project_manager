<?php

namespace App\Livewire\Report\Student;

use App\Actions\QPay\InquireAction;
use App\Exports\QPayRechargeReportExport;
use App\Models\QpayTransaction;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Every QPay top-up and refund, across all students — what parents actually paid
 * online, and what QPay said about each one.
 *
 * Reads qpay_transactions rather than the ledger on purpose: a payment that
 * failed, is still pending or is under review never reaches the books, and those
 * are exactly the rows the office needs to chase.
 */
class QPayRechargeReport extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $type = '';

    public $from_date;

    public $to_date;

    public $perPage = 25;

    public $sortField = 'qpay_transactions.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['Student-View-Refresh' => '$refresh'];

    private const SORTABLE = ['qpay_transactions.id', 'qpay_transactions.amount', 'qpay_transactions.status', 'accounts.name'];

    public function mount()
    {
        $this->from_date = date('Y-m-01');
        $this->to_date = date('Y-m-d');
    }

    public function updated($key)
    {
        if ($key !== 'perPage') {
            $this->resetPage();
        }
    }

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    /** Ask QPay for the result of a payment that never came back. */
    public function inquire($id)
    {
        abort_unless(auth()->user()?->can('report.student recharge'), 403);

        $response = (new InquireAction())->execute(QpayTransaction::findOrFail($id));
        $this->dispatch($response['success'] ? 'success' : 'error', ['message' => $response['message']]);
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('report.student recharge'), 403);

        return Excel::download(new QPayRechargeReportExport($this->filters()), 'qpay_recharges_'.now()->timestamp.'.xlsx');
    }

    public function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->status,
            'type' => $this->type,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'sort_field' => in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'qpay_transactions.id',
            'sort_direction' => $this->sortDirection === 'desc' ? 'desc' : 'asc',
        ];
    }

    public static function filteredQuery(array $filters)
    {
        return QpayTransaction::query()
            ->join('accounts', 'accounts.id', '=', 'qpay_transactions.account_id')
            ->leftJoin('student_details', 'student_details.account_id', '=', 'accounts.id')
            ->leftJoin('guardians', 'guardians.id', '=', 'qpay_transactions.guardian_id')
            ->select('qpay_transactions.*')
            ->selectRaw('accounts.name as student_name, student_details.admission_no, student_details.grade, student_details.section, guardians.name as guardian_name, guardians.mobile as guardian_mobile')
            ->when($filters['search'] ?? '', function ($q, $value) {
                $value = trim($value);

                return $q->where(function ($inner) use ($value): void {
                    $inner->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('student_details.admission_no', 'like', "%{$value}%")
                        ->orWhere('guardians.name', 'like', "%{$value}%")
                        ->orWhere('guardians.mobile', 'like', "%{$value}%")
                        ->orWhere('qpay_transactions.pun', 'like', "%{$value}%")
                        ->orWhere('qpay_transactions.confirmation_id', 'like', "%{$value}%");
                });
            })
            ->when($filters['status'] ?? '', fn ($q, $value) => $q->where('qpay_transactions.status', $value))
            ->when($filters['type'] ?? '', fn ($q, $value) => $q->where('qpay_transactions.type', $value))
            ->when($filters['from_date'] ?? '', fn ($q, $value) => $q->whereDate('qpay_transactions.created_at', '>=', $value))
            ->when($filters['to_date'] ?? '', fn ($q, $value) => $q->whereDate('qpay_transactions.created_at', '<=', $value))
            ->orderBy($filters['sort_field'] ?? 'qpay_transactions.id', $filters['sort_direction'] ?? 'desc');
    }

    public function render()
    {
        $base = fn () => self::filteredQuery($this->filters())->reorder();

        return view('livewire.report.student.qpay-recharge-report', [
            'rows' => self::filteredQuery($this->filters())->paginate($this->perPage),
            'totals' => [
                'collected' => (clone $base())->where('qpay_transactions.type', QpayTransaction::TYPE_PAYMENT)->where('qpay_transactions.status', QpayTransaction::STATUS_SUCCESS)->sum('qpay_transactions.amount'),
                'refunded' => (clone $base())->where('qpay_transactions.type', QpayTransaction::TYPE_REFUND)->whereIn('qpay_transactions.status', [QpayTransaction::STATUS_SUCCESS, QpayTransaction::STATUS_REFUND_PENDING])->sum('qpay_transactions.amount'),
                'pending' => (clone $base())->where('qpay_transactions.status', QpayTransaction::STATUS_PENDING)->count(),
                'failed' => (clone $base())->where('qpay_transactions.status', QpayTransaction::STATUS_FAILED)->count(),
                'review' => (clone $base())->where('qpay_transactions.status', QpayTransaction::STATUS_REVIEW)->count(),
                'payments' => (clone $base())->where('qpay_transactions.type', QpayTransaction::TYPE_PAYMENT)->where('qpay_transactions.status', QpayTransaction::STATUS_SUCCESS)->count(),
            ],
        ]);
    }
}
