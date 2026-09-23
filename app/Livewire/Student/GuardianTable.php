<?php

namespace App\Livewire\Student;

use App\Actions\Student\Guardian\SendInviteAction;
use App\Models\Guardian;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Every parent portal login across all students, with the children each one can
 * see. A guardian's own tenant scoping comes from BelongsToTenant on the model.
 */
class GuardianTable extends Component
{
    use WithPagination;

    public $search = '';

    public $status = '';

    public $limit = 25;

    public $sortField = 'guardians.name';

    public $sortDirection = 'asc';

    protected $paginationTheme = 'bootstrap';

    /** A set-password link generated for a parent, shown once so staff can copy it. */
    public $invite_link;

    public $invite_guardian_id;

    /** Sortable columns, so a crafted sortBy() cannot inject SQL. */
    private const SORTABLE = ['guardians.name', 'guardians.mobile', 'guardians.status'];

    public function invite($guardianId)
    {
        abort_unless(auth()->user()?->can('student guardian.invite'), 403);

        $response = (new SendInviteAction())->execute((int) $guardianId, Auth::id());
        if (! $response['success']) {
            $this->dispatch('error', ['message' => $response['message']]);

            return;
        }

        $this->invite_guardian_id = (int) $guardianId;
        $this->invite_link = $response['data']['link'];
        $this->dispatch($response['data']['delivered'] ? 'success' : 'warning', ['message' => $response['message']]);
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
        if ($key !== 'sortField' && $key !== 'sortDirection') {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset('search', 'status');
        $this->resetPage();
    }

    private function query()
    {
        return Guardian::query()
            ->when($this->search, function ($query, $value) {
                $value = trim($value);

                return $query->where(function ($q) use ($value): void {
                    $q->where('guardians.name', 'like', "%{$value}%")
                        ->orWhere('guardians.mobile', 'like', "%{$value}%")
                        ->orWhere('guardians.email', 'like', "%{$value}%")
                        ->orWhereHas('students', fn ($s) => $s->where('accounts.name', 'like', "%{$value}%"));
                });
            })
            ->when($this->status, fn ($q, $value) => $q->where('guardians.status', $value));
    }

    public function render()
    {
        $data = $this->query()
            ->with('students:accounts.id,accounts.name')
            ->orderBy(in_array($this->sortField, self::SORTABLE, true) ? $this->sortField : 'guardians.name', $this->sortDirection === 'desc' ? 'desc' : 'asc')
            ->paginate($this->limit);

        $hiddenCount = $data->total() === 0 ? Guardian::query()->count() : 0;

        return view('livewire.student.guardian-table', [
            'data' => $data,
            'hiddenCount' => $hiddenCount,
        ]);
    }
}
