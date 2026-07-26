<?php

namespace App\Livewire\RentOut\Comparison;

use App\Actions\RentOut\Comparison\CompareRentOutPopulationAction;
use App\Actions\RentOut\Comparison\StoreRentOutComparisonAction;
use App\Models\RentOutComparison;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Dashboard extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $result = 'different';

    #[Url]
    public string $verification = 'unverified';

    #[Url]
    public string $category = '';

    public int $perPage = 25;

    public ?int $selectedId = null;

    public ?string $statusMessage = null;

    protected string $paginationTheme = 'bootstrap';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'result', 'verification', 'category', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function selectRecord(int $id): void
    {
        $this->selectedId = $id;
    }

    public function closeRecord(): void
    {
        $this->selectedId = null;
    }

    public function toggleVerified(int $id): void
    {
        abort_unless(auth()->check(), 403);

        $comparison = RentOutComparison::query()->findOrFail($id);
        $comparison->update([
            'verified_at' => $comparison->verified_at ? null : now(),
            'verified_by' => $comparison->verified_at ? null : auth()->id(),
        ]);
    }

    public function runComparison(
        CompareRentOutPopulationAction $compare,
        StoreRentOutComparisonAction $store,
    ): void {
        abort_unless(auth()->check(), 403);

        $this->statusMessage = null;

        try {
            $comparison = $compare->execute(
                oldConnection: (string) config('rentout-comparison.old_connection'),
                newConnection: (string) config('rentout-comparison.new_connection'),
            );
            $stored = $store->execute($comparison);
            $this->statusMessage = "Compared and stored {$stored['stored']} records.";
            $this->resetPage();
        } catch (Throwable $throwable) {
            report($throwable);
            $this->statusMessage = 'Comparison failed. Check the application log for details.';
        }
    }

    public function render(): View
    {
        $filteredQuery = $this->filteredQuery();
        $summaryQuery = RentOutComparison::query();

        return view('livewire.rent-out.comparison.dashboard', [
            'comparisons' => $filteredQuery->orderByDesc('difference_count')->orderBy('rent_out_id')->paginate($this->perPage),
            'selectedRecord' => $this->selectedId ? RentOutComparison::query()->find($this->selectedId) : null,
            'categories' => RentOutComparison::query()->orderBy('category')->distinct()->pluck('category'),
            'summary' => [
                'total' => (clone $summaryQuery)->count(),
                'matching' => (clone $summaryQuery)->where('matches', true)->count(),
                'differing' => (clone $summaryQuery)->where('matches', false)->count(),
                'verified' => (clone $summaryQuery)->whereNotNull('verified_at')->count(),
            ],
            'lastComparedAt' => RentOutComparison::query()->max('compared_at'),
        ]);
    }

    private function filteredQuery(): Builder
    {
        return RentOutComparison::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('rent_out_id', 'like', '%'.trim($this->search).'%')
                        ->orWhere('category', 'like', '%'.trim($this->search).'%')
                        ->orWhere('status', 'like', '%'.trim($this->search).'%');
                });
            })
            ->when($this->result === 'matching', fn (Builder $query): Builder => $query->where('matches', true))
            ->when($this->result === 'different', fn (Builder $query): Builder => $query->where('matches', false))
            ->when($this->verification === 'verified', fn (Builder $query): Builder => $query->whereNotNull('verified_at'))
            ->when($this->verification === 'unverified', fn (Builder $query): Builder => $query->whereNull('verified_at'))
            ->when($this->category !== '', fn (Builder $query): Builder => $query->where('category', $this->category));
    }
}
