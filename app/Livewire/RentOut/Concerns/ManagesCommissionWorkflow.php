<?php

namespace App\Livewire\RentOut\Concerns;

use App\Actions\RentOut\Commission\ApproveAction;
use App\Actions\RentOut\Commission\CancelPaymentAction;
use App\Actions\RentOut\Commission\DeleteAction;
use App\Actions\RentOut\Commission\RejectAction;
use App\Actions\RentOut\Commission\ReopenAction;
use App\Actions\RentOut\Commission\SubmitAction;
use App\Enums\RentOut\CommissionStatus;
use App\Models\RentOutCommission;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Approval-chain buttons shared by the agreement's Commission tab and the
 * Sales → Commissions list, so both enforce the same permissions and steps.
 */
trait ManagesCommissionWorkflow
{
    /** @var array<string, string> approval step => permission */
    public static array $stagePermissions = [
        ApproveAction::STAGE_CEO => 'rent out commission.ceo approve',
        ApproveAction::STAGE_OWNER => 'rent out commission.owner approve',
        ApproveAction::STAGE_FINANCE => 'rent out commission.finance approve',
    ];

    /** Look up a commission this screen is allowed to act on. */
    abstract protected function findCommission(int $id): RentOutCommission;

    public function openPayModal(int $id): void
    {
        $this->dispatch('open-commission-pay-modal', commissionId: $this->findCommission($id)->id);
    }

    public function submit(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out commission.create'), 403);
        $this->runCommissionAction(fn () => (new SubmitAction())->execute($this->findCommission($id)->id, Auth::id()));
    }

    public function approve(int $id, string $stage): void
    {
        abort_unless(isset(self::$stagePermissions[$stage]) && auth()->user()?->can(self::$stagePermissions[$stage]), 403);
        $this->runCommissionAction(fn () => (new ApproveAction())->execute($this->findCommission($id)->id, $stage, Auth::id()));
    }

    public function reject(int $id, string $reason): void
    {
        abort_unless($this->canRejectCommissions(), 403);
        $this->runCommissionAction(fn () => (new RejectAction())->execute($this->findCommission($id)->id, $reason, Auth::id()));
    }

    public function cancelPayment(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out commission.pay'), 403);
        $this->runCommissionAction(fn () => (new CancelPaymentAction())->execute($this->findCommission($id)->id, Auth::id()));
    }

    public function reopen(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out commission.finance approve'), 403);
        $this->runCommissionAction(fn () => (new ReopenAction())->execute($this->findCommission($id)->id, Auth::id()));
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->can('rent out commission.delete'), 403);
        $this->runCommissionAction(fn () => (new DeleteAction())->execute($this->findCommission($id)->id, Auth::id()));
    }

    protected function canRejectCommissions(): bool
    {
        return collect(self::$stagePermissions)->contains(fn (string $permission): bool => (bool) auth()->user()?->can($permission));
    }

    /**
     * The approval step this user would take next on a commission, or null
     * when it is not waiting on them. Owner approval is optional, so a finance
     * approver can act on a CEO-approved commission too.
     */
    public static function nextStageFor(RentOutCommission $commission): ?string
    {
        $user = auth()->user();

        return match ($commission->status) {
            CommissionStatus::Submitted => $user?->can(self::$stagePermissions[ApproveAction::STAGE_CEO]) ? ApproveAction::STAGE_CEO : null,
            CommissionStatus::CeoApproved => $user?->can(self::$stagePermissions[ApproveAction::STAGE_OWNER])
                ? ApproveAction::STAGE_OWNER
                : ($user?->can(self::$stagePermissions[ApproveAction::STAGE_FINANCE]) ? ApproveAction::STAGE_FINANCE : null),
            CommissionStatus::OwnerApproved => $user?->can(self::$stagePermissions[ApproveAction::STAGE_FINANCE]) ? ApproveAction::STAGE_FINANCE : null,
            default => null,
        };
    }

    /**
     * Statuses waiting on the current user's approval.
     *
     * @return array<int, string>
     */
    protected function statusesWaitingOnMe(): array
    {
        $user = auth()->user();
        $statuses = [];
        if ($user?->can(self::$stagePermissions[ApproveAction::STAGE_CEO])) {
            $statuses[] = CommissionStatus::Submitted->value;
        }
        if ($user?->can(self::$stagePermissions[ApproveAction::STAGE_OWNER]) || $user?->can(self::$stagePermissions[ApproveAction::STAGE_FINANCE])) {
            $statuses[] = CommissionStatus::CeoApproved->value;
        }
        if ($user?->can(self::$stagePermissions[ApproveAction::STAGE_FINANCE])) {
            $statuses[] = CommissionStatus::OwnerApproved->value;
        }

        return $statuses;
    }

    /**
     * @param  callable(): array{success: bool, message: string}  $callback
     */
    protected function runCommissionAction(callable $callback): void
    {
        try {
            DB::beginTransaction();
            $response = $callback();
            if (! $response['success']) {
                throw new Exception($response['message'], 1);
            }
            DB::commit();
            $this->dispatch('rent-out-updated');
            $this->dispatch('success', message: $response['message']);
        } catch (\Throwable $th) {
            DB::rollback();
            $this->dispatch('error', message: $th->getMessage());
        }
    }
}
