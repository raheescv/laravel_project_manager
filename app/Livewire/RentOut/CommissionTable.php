<?php

namespace App\Livewire\RentOut;

use App\Actions\RentOut\Commission\ApproveAction;
use App\Actions\RentOut\Report\GetCommissionRowsAction;
use App\Enums\RentOut\CommissionStatus;
use App\Livewire\RentOut\Concerns\ManagesCommissionWorkflow;
use App\Models\RentOutCommission;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sales → Commissions: every agreement's commission in one approval queue.
 * "Waiting on me" shows what the signed-in approver can act on next.
 */
class CommissionTable extends Component
{
    use ManagesCommissionWorkflow, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $filterStatus = 'waiting';

    public $agentId = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $search = '';

    public $limit = 50;

    /** @var array<int, string> */
    public array $selected = [];

    #[On('rent-out-updated')]
    public function refresh(): void
    {
        $this->selected = [];
    }

    public function updating($name): void
    {
        if (in_array($name, ['filterStatus', 'agentId', 'dateFrom', 'dateTo', 'search', 'limit'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    protected function findCommission(int $id): RentOutCommission
    {
        return RentOutCommission::findOrFail($id);
    }

    /**
     * Header checkbox: tick every commission on this page that is waiting on
     * this user's approval, or clear the ticks when they are all ticked.
     */
    public function toggleSelectAll(): void
    {
        $ids = $this->approvableIds($this->pageRows());
        $this->selected = $ids && ! array_diff($ids, $this->selected) ? [] : $ids;
    }

    /**
     * Ids on a page of rows that are waiting on this user's approval.
     *
     * @param  LengthAwarePaginator<int, RentOutCommission>  $rows
     * @return array<int, string>
     */
    protected function approvableIds(LengthAwarePaginator $rows): array
    {
        return $rows->getCollection()
            ->filter(fn (RentOutCommission $commission): bool => (bool) self::nextStageFor($commission))
            ->map(fn (RentOutCommission $commission): string => (string) $commission->id)
            ->values()
            ->all();
    }

    /** @return LengthAwarePaginator<int, RentOutCommission> */
    protected function pageRows(): LengthAwarePaginator
    {
        return (new GetCommissionRowsAction())->query($this->filters())->orderByDesc('date')->orderByDesc('id')->paginate((int) $this->limit);
    }

    /**
     * Approve every selected commission at the step waiting on this user.
     */
    public function approveSelected(): void
    {
        $commissions = RentOutCommission::whereIn('id', $this->selected)->get();
        try {
            DB::beginTransaction();
            if ($commissions->isEmpty()) {
                throw new Exception('Please select the commissions to approve.', 1);
            }
            $approved = 0;
            foreach ($commissions as $commission) {
                $stage = self::nextStageFor($commission);
                if (! $stage) {
                    continue;
                }
                $response = (new ApproveAction())->execute($commission->id, $stage, Auth::id());
                if (! $response['success']) {
                    throw new Exception($response['message'], 1);
                }
                $approved++;
            }
            if (! $approved) {
                throw new Exception('None of the selected commissions are waiting on your approval.', 1);
            }
            DB::commit();
            $this->selected = [];
            $this->dispatch('success', message: $approved.' commission(s) approved.');
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', message: $th->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        $statuses = match ($this->filterStatus) {
            'waiting' => $this->statusesWaitingOnMe() ?: ['__none__'],
            'all', '' => null,
            default => [$this->filterStatus],
        };

        return [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'agentId' => $this->agentId,
            'statuses' => $statuses,
            'search' => $this->search,
        ];
    }

    public function render()
    {
        $action = new GetCommissionRowsAction();
        $base = $action->query(array_merge($this->filters(), ['statuses' => null]));

        $kpis = [
            'waiting' => $this->statusesWaitingOnMe() ? (clone $base)->whereIn('status', $this->statusesWaitingOnMe())->count() : 0,
            'unpaid' => (float) (clone $base)->where('status', CommissionStatus::FinanceApproved->value)->sum('amount'),
            'paid' => (float) (clone $base)->where('status', CommissionStatus::Paid->value)->sum('amount'),
            'total' => (float) (clone $base)->whereNotIn('status', [CommissionStatus::Rejected->value])->sum('amount'),
        ];

        $data = $this->pageRows();
        $approvableIds = $this->approvableIds($data);

        return view('livewire.rent-out.commission-table', [
            'data' => $data,
            'approvableIds' => $approvableIds,
            'kpis' => $kpis,
            'canReject' => $this->canRejectCommissions(),
            'statusTabs' => ['waiting' => 'Waiting on me'] + CommissionStatus::options() + ['all' => 'All'],
        ]);
    }
}
