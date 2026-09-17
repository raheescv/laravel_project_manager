<?php

namespace App\Livewire\Student;

use App\Actions\Student\DeleteAction;
use App\Actions\Student\GetBalanceAction;
use App\Exports\StudentExport;
use App\Models\Account;
use App\Models\StudentDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Students list. A student is an account (model = student) joined to its
 * student_details row; the balance column is each account's own ledger.
 */
class Table extends Component
{
    use WithPagination;

    public $search = '';

    public $limit = 25;

    public $grade = '';

    public $section = '';

    public $status = 'active';

    public $card_status = '';

    public $selected = [];

    public $selectAll = false;

    public $sortField = 'accounts.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'Student-Refresh-Component' => '$refresh',
    ];

    /** Sortable columns, so a crafted sortBy() cannot inject SQL. */
    private const SORTABLE = ['accounts.name', 'student_details.admission_no', 'student_details.grade', 'student_details.status', 'accounts.id'];

    public function delete()
    {
        abort_unless(auth()->user()?->can('student.delete'), 403);
        try {
            DB::beginTransaction();
            if (! count($this->selected)) {
                throw new \Exception('Please select any student to delete.', 1);
            }
            foreach ($this->selected as $id) {
                $response = (new DeleteAction())->execute((int) $id, Auth::id());
                if (! $response['success']) {
                    throw new \Exception($response['message'], 1);
                }
            }
            DB::commit();
            $this->dispatch('success', ['message' => 'Successfully Deleted '.count($this->selected).' students']);
            $this->selected = [];
            $this->selectAll = false;
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function export()
    {
        abort_unless(auth()->user()?->can('student.export'), 403);

        return Excel::download(new StudentExport($this->filters()), 'students_'.now()->timestamp.'.xlsx');
    }

    public function updatedSelectAll($value)
    {
        $this->selected = $value ? $this->query()->limit(2000)->pluck('accounts.id')->map(fn ($id) => (string) $id)->all() : [];
    }

    public function sortBy($field)
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updated($key)
    {
        if (! in_array($key, ['selected', 'selectAll'], true)) {
            $this->resetPage();
        }
    }

    /** "All statuses", not the Active default: this is the way back to students the filters hid. */
    public function clearFilters()
    {
        $this->reset('search', 'grade', 'section', 'card_status');
        $this->status = '';
        $this->resetPage();
    }

    public function filters(): array
    {
        return [
            'search' => $this->search,
            'grade' => $this->grade,
            'section' => $this->section,
            'status' => $this->status,
            'card_status' => $this->card_status,
        ];
    }

    public static function filteredQuery(array $filters)
    {
        return Account::student()
            ->join('student_details', 'student_details.account_id', '=', 'accounts.id')
            ->when($filters['search'] ?? '', function ($query, $value) {
                $value = trim($value);

                return $query->where(function ($q) use ($value): void {
                    $q->where('accounts.name', 'like', "%{$value}%")
                        ->orWhere('student_details.admission_no', 'like', "%{$value}%")
                        ->orWhere('accounts.mobile', 'like', "%{$value}%")
                        ->orWhere('student_details.card_uid', 'like', '%'.StudentDetail::normalizeCardUid($value).'%')
                        ->orWhereHas('guardians', fn ($g) => $g->where('guardians.name', 'like', "%{$value}%")->orWhere('guardians.mobile', 'like', "%{$value}%"));
                });
            })
            ->when($filters['grade'] ?? '', fn ($q, $value) => $q->where('student_details.grade', $value))
            ->when($filters['section'] ?? '', fn ($q, $value) => $q->where('student_details.section', $value))
            ->when($filters['status'] ?? '', fn ($q, $value) => $q->where('student_details.status', $value))
            ->when($filters['card_status'] ?? '', function ($q, $value) {
                return $value === 'none'
                    ? $q->whereNull('student_details.card_uid')
                    : $q->whereNotNull('student_details.card_uid')->where('student_details.card_status', $value);
            });
    }

    private function query()
    {
        return self::filteredQuery($this->filters());
    }

    public function render()
    {
        $data = $this->query()
            ->with(['guardians' => fn ($q) => $q->orderByDesc('guardian_student.is_primary')])
            ->select('accounts.*', 'student_details.admission_no', 'student_details.grade', 'student_details.section', 'student_details.status as student_status', 'student_details.card_uid', 'student_details.card_status')
            ->orderBy(in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'accounts.name', $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->paginate($this->limit);

        $balances = (new GetBalanceAction())->many($data->pluck('id')->all());

        // The list opens on Active only, so an empty page must say what the filters are hiding.
        $hiddenCount = $data->total() === 0 ? self::filteredQuery([])->count() : 0;

        return view('livewire.student.table', [
            'data' => $data,
            'balances' => $balances,
            'hiddenCount' => $hiddenCount,
            'grades' => StudentDetail::whereNotNull('grade')->distinct()->orderBy('grade')->pluck('grade'),
            'sections' => StudentDetail::whereNotNull('section')->distinct()->orderBy('section')->pluck('section'),
        ]);
    }
}
