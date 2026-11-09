<?php

namespace App\Livewire\Student;

use App\Actions\Student\Guardian\SendInviteAction;
use App\Actions\Student\Registration\ApproveAction;
use App\Actions\Student\Registration\RejectAction;
use App\Models\Guardian;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The office's inbox for the public registration form: one row per family with
 * pending students. Approve makes them active and invites a new parent; reject
 * removes what the form created.
 */
class RegistrationTable extends Component
{
    use WithPagination;

    /** Most families "select all" picks up in one go. */
    public const MAX_SELECTION = 500;

    public $search = '';

    public $school = '';

    /** '' for all, 'new' or 'existing'. */
    public $parentType = '';

    public $limit = 25;

    public $selected = [];

    public $selectAll = false;

    #[Locked]
    public ?int $viewingId = null;

    protected $paginationTheme = 'bootstrap';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'school', 'parentType', 'limit'], true)) {
            $this->resetPage();
            $this->clearSelection();
        }
    }

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->filteredQuery()->limit(self::MAX_SELECTION)->pluck('guardians.id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function viewFamily($guardianId): void
    {
        abort_unless(auth()->user()?->can('student registration.view'), 403);

        $this->viewingId = (int) $guardianId;
        $this->dispatch('show-registration-modal');
    }

    public function approve($guardianId): void
    {
        abort_unless(auth()->user()?->can('student registration.approve'), 403);

        $response = $this->approveFamily((int) $guardianId);

        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->forgetSelected((int) $guardianId);
        $this->dispatch('hide-registration-modal');
        $this->inviteNewParent($response['data'], $response['message']);
    }

    /**
     * Each family saves on its own, so one that cannot be approved never holds up
     * the rest. Links go out after the page has answered, because each one is a
     * live email and WhatsApp send.
     */
    public function approveSelected(): void
    {
        abort_unless(auth()->user()?->can('student registration.approve'), 403);

        $approved = 0;
        $firstProblem = null;
        $failed = 0;
        $toInvite = [];

        foreach ($this->selected as $guardianId) {
            $response = $this->approveFamily((int) $guardianId);

            if (! $response['success']) {
                $failed++;
                $firstProblem ??= $response['message'];

                continue;
            }

            $approved++;
            if ($response['data']->isPendingInvite() && $response['data']->isActive()) {
                $toInvite[] = $response['data']->id;
            }
        }

        $this->clearSelection();

        if ($toInvite !== []) {
            $userId = Auth::id();
            app()->terminating(function () use ($toInvite, $userId): void {
                foreach ($toInvite as $guardianId) {
                    (new SendInviteAction())->execute($guardianId, $userId);
                }
            });
        }

        $summary = $approved === 1 ? '1 family approved.' : "{$approved} families approved.";
        $summary .= $toInvite === [] ? '' : ' New parents are being sent their portal link.';

        if ($failed === 0) {
            $this->dispatch('success', ['message' => $summary]);

            return;
        }

        $this->dispatch($approved === 0 ? 'error' : 'warning', [
            'message' => "{$summary} {$failed} could not be approved: {$firstProblem}",
        ]);
    }

    public function reject($guardianId): void
    {
        abort_unless(auth()->user()?->can('student registration.approve'), 403);

        try {
            DB::beginTransaction();
            $response = (new RejectAction())->execute((int) $guardianId, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            $this->forgetSelected((int) $guardianId);
            $this->dispatch('hide-registration-modal');
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    /**
     * @return array{success: bool, message: string, data?: Guardian}
     */
    private function approveFamily(int $guardianId): array
    {
        try {
            DB::beginTransaction();
            $response = (new ApproveAction())->execute($guardianId, Auth::id());
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();

            return $response;
        } catch (\Throwable $th) {
            DB::rollback();

            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    private function forgetSelected(int $guardianId): void
    {
        $this->selected = array_values(array_diff($this->selected, [(string) $guardianId]));
    }

    /** A parent who already has a password needs no new link. */
    private function inviteNewParent(Guardian $guardian, string $added): void
    {
        if (! $guardian->isPendingInvite() || ! $guardian->isActive()) {
            $this->dispatch('success', ['message' => $added.'. This parent already has a portal login.']);

            return;
        }

        $invite = (new SendInviteAction())->execute($guardian->id, Auth::id());
        $delivered = $invite['success'] && $invite['data']['delivered'];
        $this->dispatch($delivered ? 'success' : 'warning', ['message' => $added.'. '.$invite['message']]);
    }

    private function filteredQuery(): Builder
    {
        return Guardian::query()
            ->whereHas('pendingStudents', fn ($students) => $students->when(
                $this->school !== '',
                fn ($query) => $query->whereHas('studentDetail', fn ($detail) => $detail->where('school', $this->school)),
            ))
            ->when($this->parentType === 'new', fn ($query) => $query->where('guardians.status', Guardian::PENDING))
            ->when($this->parentType === 'existing', fn ($query) => $query->where('guardians.status', '!=', Guardian::PENDING))
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($inner) => $inner
                    ->where('guardians.name', 'like', $term)
                    ->orWhere('guardians.mobile', 'like', $term)
                    ->orWhere('guardians.id_no', 'like', $term)
                    ->orWhere('guardians.email', 'like', $term)
                    ->orWhereHas('pendingStudents', fn ($students) => $students->where('accounts.name', 'like', $term)));
            })
            ->orderByDesc('guardians.updated_at');
    }

    /**
     * What the bulk bar and its confirmation say about the ticked families.
     *
     * @return array{families: int, students: int, existing: int}
     */
    private function selectionSummary(): array
    {
        if ($this->selected === []) {
            return ['families' => 0, 'students' => 0, 'existing' => 0];
        }

        $families = Guardian::query()
            ->whereIn('guardians.id', $this->selected)
            ->whereHas('pendingStudents')
            ->withCount('pendingStudents')
            ->get();

        return [
            'families' => $families->count(),
            'students' => (int) $families->sum('pending_students_count'),
            'existing' => $families->reject(fn (Guardian $guardian) => $guardian->isPendingApproval())->count(),
        ];
    }

    public function render(): View
    {
        $waiting = Guardian::query()->whereHas('pendingStudents')->with('pendingStudents.studentDetail')->get();

        return view('livewire.student.registration-table', [
            'stats' => [
                'families' => $waiting->count(),
                'students' => $waiting->sum(fn (Guardian $guardian) => $guardian->pendingStudents->count()),
                'existing' => $waiting->reject(fn (Guardian $guardian) => $guardian->isPendingApproval())->count(),
            ],
            'schools' => $waiting
                ->flatMap(fn (Guardian $guardian) => $guardian->pendingStudents->map(fn ($student) => $student->studentDetail->school)->filter()->unique())
                ->countBy()
                ->sortKeys(),
            'data' => $this->filteredQuery()->with('pendingStudents.studentDetail')->paginate($this->limit),
            'selection' => $this->selectionSummary(),
            'viewing' => $this->viewingId
                ? Guardian::with('pendingStudents.studentDetail')->find($this->viewingId)
                : null,
        ]);
    }
}
