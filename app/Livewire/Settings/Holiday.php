<?php

namespace App\Livewire\Settings;

use App\Actions\Settings\Holiday\CreateAction;
use App\Actions\Settings\Holiday\DeleteAction;
use App\Actions\Settings\Holiday\UpdateAction;
use App\Models\Holiday as HolidayModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * The company holiday calendar: the dates the business is closed regardless of
 * what the working week says.
 *
 * It sits beside Settings -> Working Day because the two answer one question
 * between them — the week says which days we open, this says which of those
 * days we are shut anyway. The appointment scheduler reads both, so a date
 * added here disappears from every salesman's bookable slots at once.
 */
class Holiday extends Component
{
    /** Which year's holidays are listed. Recurring ones show in every year. */
    public $year;

    public $editingId = null;

    public $name = '';

    public $date = '';

    public $is_recurring = false;

    public $note = '';

    public function mount(): void
    {
        $this->year = (int) date('Y');
        $this->date = date('Y-m-d');
    }

    /** The years offered in the picker — this one, plus a reach either side. */
    public function getYearsProperty(): array
    {
        $current = (int) date('Y');

        return range($current - 2, $current + 3);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $data = [
            'name' => trim((string) $this->name),
            'date' => $this->date,
            'is_recurring' => (bool) $this->is_recurring,
            'note' => trim((string) $this->note) ?: null,
        ];

        $this->run(function () use ($data) {
            return $this->editingId
                ? (new UpdateAction())->execute($data, $this->editingId, Auth::id())
                : (new CreateAction())->execute($data, Auth::id());
        }, onSuccess: function () {
            // Land the list on the year the holiday was filed under, so the row
            // just saved is the one the user is looking at.
            $this->year = (int) Carbon::parse($this->date)->year;
            $this->resetForm();
        });
    }

    public function edit($id): void
    {
        $holiday = HolidayModel::find($id);

        if (! $holiday) {
            return;
        }

        $this->editingId = $holiday->id;
        $this->name = $holiday->name;
        $this->date = $holiday->date->toDateString();
        $this->is_recurring = (bool) $holiday->is_recurring;
        $this->note = (string) $holiday->note;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    /** Switch a holiday off without losing it — next year it may be back. */
    public function toggleActive($id): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $holiday = HolidayModel::find($id);

        if (! $holiday) {
            return;
        }

        $this->run(fn () => (new UpdateAction())->execute([
            'name' => $holiday->name,
            'date' => $holiday->date->toDateString(),
            'is_recurring' => $holiday->is_recurring,
            'is_active' => ! $holiday->is_active,
            'note' => $holiday->note,
        ], $holiday->id, Auth::id()));
    }

    public function remove($id): void
    {
        abort_unless(auth()->user()?->can('configuration.settings'), 403);

        $this->run(fn () => (new DeleteAction())->execute($id, Auth::id()), onSuccess: function () use ($id) {
            if ($this->editingId == $id) {
                $this->resetForm();
            }
        });
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->date = date('Y-m-d');
        $this->is_recurring = false;
        $this->note = '';
    }

    private function run(callable $callback, ?callable $onSuccess = null): void
    {
        try {
            DB::beginTransaction();
            $response = $callback();
            if (! $response['success']) {
                throw new \Exception($response['message'], 1);
            }
            DB::commit();
            if ($onSuccess) {
                $onSuccess();
            }
            $this->dispatch('success', ['message' => $response['message']]);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', ['message' => $th->getMessage()]);
        }
    }

    public function render()
    {
        $year = (int) $this->year;
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->startOfDay();

        // Recurring rows are listed whatever year they were first entered in,
        // so the query cannot simply filter on the stored date.
        $holidays = HolidayModel::query()
            ->where(fn ($query) => $query->where('is_recurring', true)->orWhereBetween('date', [$start->toDateString(), $end->toDateString()]))
            ->orderBy('date')
            ->get()
            ->map(function (HolidayModel $holiday) use ($year) {
                // The date this row actually falls on in the year being viewed —
                // what the list sorts by, and what the calendar will show.
                return ['model' => $holiday, 'occurs' => $holiday->occurrenceIn($year)];
            })
            ->sortBy(fn ($row) => $row['occurs']->toDateString())
            ->values();

        return view('livewire.settings.holiday', [
            'holidays' => $holidays,
            'upcoming' => $holidays->filter(fn ($row) => $row['occurs']->gte(now()->startOfDay()) && $row['model']->is_active)->count(),
        ]);
    }
}
