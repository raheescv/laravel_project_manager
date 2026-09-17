<?php

namespace App\Livewire\Report\Student;

use App\Exports\StudentWalletReportExport;
use App\Models\Account;
use App\Models\StudentDetail;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Every student's card balance, and what moved over the chosen period.
 *
 * Read straight from the ledger — the same source as a student's own statement
 * and the POS balance check — so the closing column always adds up to the
 * "Student Card Balances" total in the books.
 */
class WalletReport extends Component
{
    use WithPagination;

    public $search = '';

    public $grade = '';

    public $section = '';

    public $status = 'active';

    public $from_date;

    public $to_date;

    /** Only cards in overdraft (a negative balance). */
    public $overdrawn_only = false;

    public $perPage = 25;

    public $sortField = 'accounts.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    private const SORTABLE = ['accounts.name', 'student_details.admission_no', 'student_details.grade', 'closing_balance', 'period_in', 'period_out'];

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

    public function export()
    {
        abort_unless(auth()->user()?->can('report.student wallet'), 403);

        return Excel::download(new StudentWalletReportExport($this->filters()), 'student_wallet_'.now()->timestamp.'.xlsx');
    }

    public function filters(): array
    {
        return [
            'search' => $this->search,
            'grade' => $this->grade,
            'section' => $this->section,
            'status' => $this->status,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'overdrawn_only' => $this->overdrawn_only,
            'sort_field' => in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'accounts.name',
            'sort_direction' => $this->sortDirection === 'desc' ? 'desc' : 'asc',
        ];
    }

    /**
     * Students with their opening balance, the period's movement and the closing
     * balance, in one query.
     *
     * The ledger is joined by hand rather than through the model, so `tenant_id`
     * is stated explicitly: a raw join carries no global scope (see the
     * multi-tenancy skill).
     */
    public static function filteredQuery(array $filters)
    {
        $tenantId = app(TenantService::class)->getCurrentTenantId();
        $from = $filters['from_date'] ?: null;
        $to = $filters['to_date'] ?: null;

        $movement = function (string $column, ?string $start, ?string $end) use ($tenantId) {
            $sql = "(SELECT COALESCE(SUM(je.{$column}), 0) FROM journal_entries je WHERE je.account_id = accounts.id AND je.tenant_id = ? AND je.deleted_at IS NULL";
            $bindings = [$tenantId];
            if ($start) {
                $sql .= ' AND je.date >= ?';
                $bindings[] = $start;
            }
            if ($end) {
                $sql .= ' AND je.date <= ?';
                $bindings[] = $end;
            }

            return [$sql.')', $bindings];
        };

        [$openingCredit, $b1] = $movement('credit', null, $from ? date('Y-m-d', strtotime($from.' -1 day')) : null);
        [$openingDebit, $b2] = $movement('debit', null, $from ? date('Y-m-d', strtotime($from.' -1 day')) : null);
        [$periodIn, $b3] = $movement('credit', $from, $to);
        [$periodOut, $b4] = $movement('debit', $from, $to);
        [$allCredit, $b5] = $movement('credit', null, null);
        [$allDebit, $b6] = $movement('debit', null, null);

        $opening = "(accounts.opening_credit - accounts.opening_debit + {$openingCredit} - {$openingDebit})";
        $closing = "(accounts.opening_credit - accounts.opening_debit + {$allCredit} - {$allDebit})";

        $query = Account::student()
            ->join('student_details', 'student_details.account_id', '=', 'accounts.id')
            ->selectRaw('accounts.id, accounts.name, accounts.mobile, student_details.admission_no, student_details.grade, student_details.section, student_details.status as student_status, student_details.card_uid, student_details.card_status')
            ->selectRaw("{$opening} as opening_balance", [...$b1, ...$b2])
            ->selectRaw("{$periodIn} as period_in", $b3)
            ->selectRaw("{$periodOut} as period_out", $b4)
            ->selectRaw("{$closing} as closing_balance", [...$b5, ...$b6])
            ->when($filters['search'] ?? '', function ($q, $value) {
                $value = trim($value);

                return $q->where(function ($inner) use ($value): void {
                    $inner->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('student_details.admission_no', 'like', "%{$value}%")
                        ->orWhere('student_details.card_uid', 'like', '%'.StudentDetail::normalizeCardUid($value).'%');
                });
            })
            ->when($filters['grade'] ?? '', fn ($q, $value) => $q->where('student_details.grade', $value))
            ->when($filters['section'] ?? '', fn ($q, $value) => $q->where('student_details.section', $value))
            ->when($filters['status'] ?? '', fn ($q, $value) => $q->where('student_details.status', $value))
            ->when($filters['overdrawn_only'] ?? false, fn ($q) => $q->havingRaw('closing_balance < 0'));

        return $query->orderBy($filters['sort_field'] ?? 'accounts.name', $filters['sort_direction'] ?? 'asc');
    }

    public function render()
    {
        $rows = self::filteredQuery($this->filters())->paginate($this->perPage);

        // Totals over the whole filtered set, not just the page on screen.
        $totals = DB::query()
            ->fromSub(self::filteredQuery($this->filters())->reorder(), 'report')
            ->selectRaw('COUNT(*) as students, COALESCE(SUM(opening_balance), 0) as opening, COALESCE(SUM(period_in), 0) as period_in, COALESCE(SUM(period_out), 0) as period_out, COALESCE(SUM(closing_balance), 0) as closing, COALESCE(SUM(CASE WHEN closing_balance < 0 THEN closing_balance ELSE 0 END), 0) as overdrawn, COALESCE(SUM(CASE WHEN closing_balance < 0 THEN 1 ELSE 0 END), 0) as overdrawn_students')
            ->first();

        return view('livewire.report.student.wallet-report', [
            'rows' => $rows,
            'totals' => $totals,
            'grades' => StudentDetail::whereNotNull('grade')->distinct()->orderBy('grade')->pluck('grade'),
            'sections' => StudentDetail::whereNotNull('section')->distinct()->orderBy('section')->pluck('section'),
        ]);
    }
}
