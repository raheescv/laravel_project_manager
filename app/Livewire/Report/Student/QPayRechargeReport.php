<?php

namespace App\Livewire\Report\Student;

use App\Actions\QPay\InquireAction;
use App\Actions\QPay\ReleaseAction;
use App\Exports\QPayRechargeReportExport;
use App\Livewire\Concerns\HasReportPeriod;
use App\Models\QpayTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Every online top-up and refund, across all students — debit card (QPay) and
 * credit card (Mastercard Gateway) — what parents actually paid online, and what
 * the gateway said about each one.
 *
 * Reads qpay_transactions rather than the ledger on purpose: a payment that
 * failed, is still pending or is under review never reaches the books, and those
 * are exactly the rows the office needs to chase.
 */
class QPayRechargeReport extends Component
{
    use HasReportPeriod;
    use WithPagination;

    public $search = '';

    /** @var list<string> Empty means every status. */
    public $status = [];

    public $type = '';

    /** '' · qpay (debit card) · mpgs (credit card) */
    public $gateway = '';

    public $from_date;

    public $to_date;

    public $perPage = 25;

    /** The transaction open in the details popup. */
    public ?int $detailId = null;

    public $sortField = 'qpay_transactions.id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = ['Student-View-Refresh' => '$refresh'];

    private const SORTABLE = ['qpay_transactions.id', 'qpay_transactions.amount', 'qpay_transactions.status', 'accounts.name'];

    /**
     * Summary card → the [statuses, type] filter it applies.
     *
     * @var array<string, array{0: list<string>, 1: string}>
     */
    public const CARDS = [
        'payments' => [[QpayTransaction::STATUS_SUCCESS], QpayTransaction::TYPE_PAYMENT],
        'refunded' => [[QpayTransaction::STATUS_SUCCESS, QpayTransaction::STATUS_REFUND_PENDING], QpayTransaction::TYPE_REFUND],
        'pending' => [[QpayTransaction::STATUS_PENDING], ''],
        'failed' => [[QpayTransaction::STATUS_FAILED], ''],
        'review' => [[QpayTransaction::STATUS_REVIEW], ''],
    ];

    /** @var array<string, string> */
    public const STATUSES = [
        'success' => 'Successful',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'review' => 'Needs review',
        'unresolved' => 'Unresolved',
        'refunded' => 'Refunded',
        'refund_pending' => 'Refund pending',
    ];

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

    /** A summary card filters the list; pressing the active card again (or Collected) shows everything. */
    public function filterCard(string $card): void
    {
        [$status, $type] = self::CARDS[$card] ?? [[], ''];
        if ($this->activeCard() === $card) {
            [$status, $type] = [[], ''];
        }
        $this->status = $status;
        $this->type = $type;
        $this->resetPage();
    }

    /** The summary card matching the current status + type filters, '' when none does. */
    public function activeCard(): string
    {
        foreach (self::CARDS as $card => [$status, $type]) {
            if ($this->selectedStatuses() === $status && $this->type === $type) {
                return $card;
            }
        }

        return '';
    }

    /** Status chips are multi-select; an empty selection means every status. */
    public function toggleStatus(string $status): void
    {
        if ($status === '') {
            $this->status = [];
        } elseif (isset(self::STATUSES[$status])) {
            $selected = $this->selectedStatuses();
            $this->status = in_array($status, $selected, true)
                ? array_values(array_diff($selected, [$status]))
                : [...$selected, $status];
        }
        $this->resetPage();
    }

    /** @return list<string> */
    public function selectedStatuses(): array
    {
        return array_values(array_intersect(array_keys(self::STATUSES), (array) $this->status));
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'type', 'gateway']);
        $this->setRange('this_month');
    }

    /** Ask the gateway for the result of a payment that never came back. */
    public function inquire($id)
    {
        abort_unless(auth()->user()?->can('report.student recharge'), 403);

        $response = (new InquireAction())->execute(QpayTransaction::findOrFail($id));
        $this->dispatch($response['success'] ? 'success' : 'error', ['message' => $response['message']]);
    }

    /** Free the card from a payment QPay will not answer for, so the parent can top up again. */
    public function release($id)
    {
        abort_unless(auth()->user()?->can('student topup.release'), 403);

        $response = (new ReleaseAction())->execute(QpayTransaction::findOrFail($id), Auth::id());
        $this->dispatch($response['success'] ? 'success' : 'error', ['message' => $response['message']]);
    }

    public function showDetails(int $id): void
    {
        abort_unless(auth()->user()?->can('report.student recharge'), 403);

        $this->detailId = QpayTransaction::query()->whereKey($id)->value('id');
    }

    public function closeDetails(): void
    {
        $this->detailId = null;
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('report.student recharge'), 403);

        return Excel::download(new QPayRechargeReportExport($this->filters()), 'online_recharges_'.now()->timestamp.'.xlsx');
    }

    public function filters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->selectedStatuses(),
            'type' => $this->type,
            'gateway' => in_array($this->gateway, [QpayTransaction::GATEWAY_QPAY, QpayTransaction::GATEWAY_MPGS], true) ? $this->gateway : '',
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
            ->when((array) ($filters['status'] ?? []), fn ($q, $value) => $q->whereIn('qpay_transactions.status', $value))
            ->when($filters['type'] ?? '', fn ($q, $value) => $q->where('qpay_transactions.type', $value))
            ->when($filters['gateway'] ?? '', fn ($q, $value) => $q->where('qpay_transactions.gateway', $value))
            ->when($filters['from_date'] ?? '', fn ($q, $value) => $q->whereDate('qpay_transactions.created_at', '>=', $value))
            ->when($filters['to_date'] ?? '', fn ($q, $value) => $q->whereDate('qpay_transactions.created_at', '<=', $value))
            ->orderBy($filters['sort_field'] ?? 'qpay_transactions.id', $filters['sort_direction'] ?? 'desc');
    }

    public function render()
    {
        $base = fn () => self::filteredQuery(['status' => [], 'type' => ''] + $this->filters())->reorder();
        $detail = $this->detailId
            ? QpayTransaction::query()->with(['account:id,name', 'account.studentDetail', 'guardian:id,name,mobile,email'])->find($this->detailId)
            : null;
        $detailLogs = $detail?->apiLogs();

        return view('livewire.report.student.qpay-recharge-report', [
            'rows' => self::filteredQuery($this->filters())->paginate($this->perPage),
            'ranges' => self::RANGES,
            'activeRange' => $this->currentRange(),
            'activeCard' => $this->activeCard(),
            'statuses' => self::STATUSES,
            'selectedStatuses' => $this->selectedStatuses(),
            'detail' => $detail,
            'detailLogs' => $detailLogs,
            'detailRelated' => $detail
                ? QpayTransaction::query()
                    ->whereKeyNot($detail->id)
                    ->where(fn ($query) => $query->whereIn('pun', $detail->relatedPuns())->orWhereIn('original_pun', $detail->relatedPuns()))
                    ->orderBy('id')
                    ->get()
                : collect(),
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
