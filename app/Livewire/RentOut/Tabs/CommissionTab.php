<?php

namespace App\Livewire\RentOut\Tabs;

use App\Enums\RentOut\CommissionStatus;
use App\Livewire\RentOut\Concerns\ManagesCommissionWorkflow;
use App\Models\RentOutCommission;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Agent commission on a sale agreement and its approval chain:
 * Draft → Submitted → CEO → Owner (optional) → Finance (posts journal) → Paid.
 *
 * Shows one commission in detail (progress, figures, documents, activity);
 * when an agreement has more than one, a list below switches between them.
 */
class CommissionTab extends Component
{
    use ManagesCommissionWorkflow;

    public $rentOutId;

    public ?int $selectedId = null;

    /** Approval order the progress bar walks; rejected sits back at draft. */
    private const ORDER = [
        CommissionStatus::Draft,
        CommissionStatus::Submitted,
        CommissionStatus::CeoApproved,
        CommissionStatus::OwnerApproved,
        CommissionStatus::FinanceApproved,
        CommissionStatus::Paid,
    ];

    public function mount($rentOutId)
    {
        $this->rentOutId = $rentOutId;
    }

    #[On('rent-out-updated')]
    public function refresh() {}

    public function openCommissionModal(?int $id = null): void
    {
        $this->dispatch('open-commission-modal', rentOutId: $this->rentOutId, commissionId: $id);
    }

    public function selectCommission(int $id): void
    {
        $this->selectedId = $this->findCommission($id)->id;
    }

    protected function findCommission(int $id): RentOutCommission
    {
        return RentOutCommission::where('rent_out_id', $this->rentOutId)->findOrFail($id);
    }

    /**
     * Progress bar steps for a commission.
     *
     * @return array<int, array{label: string, sub: string, state: string}>
     */
    protected function steps(RentOutCommission $commission): array
    {
        $reached = $commission->status === CommissionStatus::Rejected ? 0 : (int) array_search($commission->status, self::ORDER, true);
        $stamp = fn (?CarbonInterface $at, ?string $name): string => $at ? trim($at->format('d M').($name ? ' · '.$name : '')) : '';
        $ownerSkipped = $reached >= 4 && ! $commission->owner_approved_at;

        $steps = [
            ['label' => 'Draft', 'sub' => $stamp($commission->created_at, $commission->creator?->name)],
            ['label' => 'Submitted', 'sub' => $stamp($commission->submitted_at, $commission->submitter?->name)],
            ['label' => 'CEO Approved', 'sub' => $stamp($commission->ceo_approved_at, $commission->ceoApprover?->name)],
            ['label' => 'Owner Approval', 'sub' => $ownerSkipped ? 'optional · skipped' : ($commission->owner_approved_at ? $stamp($commission->owner_approved_at, $commission->ownerApprover?->name) : 'optional')],
            ['label' => 'Finance Approved', 'sub' => $commission->finance_approved_at ? $stamp($commission->finance_approved_at, $commission->financeApprover?->name) : 'posts journal'],
            ['label' => 'Paid', 'sub' => $commission->paid_date ? $stamp($commission->paid_date, $commission->paymentAccount?->name) : 'cash / bank'],
        ];

        foreach ($steps as $index => &$step) {
            $step['state'] = match (true) {
                $index === 3 && $ownerSkipped => 'skipped',
                $index <= $reached => 'done',
                $index === $reached + 1 => 'current',
                default => 'pending',
            };
            if ($step['state'] === 'current' && $step['sub'] === '') {
                $step['sub'] = 'waiting';
            }
        }

        return $steps;
    }

    /**
     * What happened to a commission, newest first, read from its stamps. The
     * chain only moves forward (a rejection clears later approvals), so step
     * order is time order.
     *
     * @return Collection<int, array{at: CarbonInterface, title: string, detail: string, tone: string}>
     */
    protected function activity(RentOutCommission $commission): Collection
    {
        $amount = number_format((float) $commission->amount, 2);
        $rate = $commission->commission_type === RentOutCommission::TYPE_FIXED
            ? 'fixed '.$amount
            : number_format((float) $commission->commission_percentage, 2).'% of contract';

        return collect([
            [$commission->created_at, 'Commission entered', trim($rate.' by '.($commission->creator?->name ?? '')), 'secondary'],
            [$commission->submitted_at, 'Submitted', 'by '.$commission->submitter?->name, 'info'],
            [$commission->ceo_approved_at, 'CEO approved', 'by '.$commission->ceoApprover?->name, 'success'],
            [$commission->owner_approved_at, 'Owner approved', 'by '.$commission->ownerApprover?->name, 'success'],
            [$commission->finance_approved_at, 'Finance approved', 'by '.$commission->financeApprover?->name.($commission->invoice_no ? ' · '.$commission->invoice_no : ''), 'success'],
            [$commission->paid_date, 'Paid', $amount.' via '.$commission->paymentAccount?->name, 'success'],
            [$commission->rejected_at, 'Rejected', 'by '.$commission->rejecter?->name.' — "'.$commission->rejection_reason.'"', 'danger'],
        ])
            ->filter(fn (array $event): bool => $event[0] !== null)
            ->map(fn (array $event): array => ['at' => $event[0], 'title' => $event[1], 'detail' => $event[2], 'tone' => $event[3]])
            ->reverse()
            ->values();
    }

    public function render()
    {
        $commissions = RentOutCommission::with(['agent:id,name', 'creator:id,name', 'submitter:id,name', 'ceoApprover:id,name', 'ownerApprover:id,name', 'financeApprover:id,name', 'rejecter:id,name', 'paymentAccount:id,name'])
            ->where('rent_out_id', $this->rentOutId)
            ->orderBy('id')
            ->get();

        $selected = $commissions->firstWhere('id', $this->selectedId) ?? $commissions->last();
        $active = $commissions->where('status', '!=', CommissionStatus::Rejected);

        return view('livewire.rent-out.tabs.commission-tab', [
            'commissions' => $commissions,
            'selected' => $selected,
            'steps' => $selected ? $this->steps($selected) : [],
            'activity' => $selected ? $this->activity($selected) : collect(),
            'canAdd' => $active->isEmpty(),
            'canReject' => $this->canRejectCommissions(),
            'totals' => [
                'total' => (float) $active->sum('amount'),
                'paid' => (float) $active->where('status', CommissionStatus::Paid)->sum('amount'),
                'payable' => (float) $active->where('status', CommissionStatus::FinanceApproved)->sum('amount'),
            ],
        ]);
    }
}
