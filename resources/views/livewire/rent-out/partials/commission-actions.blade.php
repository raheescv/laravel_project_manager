{{--
    Approval-chain buttons for one commission, shared by the agreement's
    Commission tab and Sales → Commissions. The host component uses
    ManagesCommissionWorkflow.

    $canEdit   — show edit/submit/delete (only where the Add/Edit modal is on the page)
    $labelled  — full buttons with text (detail header) instead of compact row icons
--}}
@php
    use App\Actions\RentOut\Commission\ApproveAction;
    use App\Enums\RentOut\CommissionStatus;

    $status = $commission->status;
    $id = $commission->id;
    $labelled = $labelled ?? false;
    $user = auth()->user();

    $buttons = [];
    if ($canEdit && $status->isEditable()) {
        if ($user?->can('rent out commission.edit')) {
            $buttons[] = ['label' => 'Edit', 'short' => null, 'icon' => 'fa-pencil', 'tone' => 'secondary', 'click' => "openCommissionModal({$id})"];
        }
        if ($status === CommissionStatus::Draft && $user?->can('rent out commission.create')) {
            $buttons[] = ['label' => 'Submit for Approval', 'short' => null, 'icon' => 'fa-send', 'tone' => 'primary', 'click' => "submit({$id})", 'confirm' => 'Submit this commission for approval?', 'primary' => true];
        }
        if ($user?->can('rent out commission.delete')) {
            $buttons[] = ['label' => 'Delete', 'short' => null, 'icon' => 'fa-trash', 'tone' => 'danger', 'click' => "delete({$id})", 'confirm' => 'Are you sure you want to delete this commission?'];
        }
    }
    if ($status->isRejectable() && $canReject) {
        $buttons[] = ['label' => 'Reject', 'short' => null, 'icon' => 'fa-ban', 'tone' => 'danger', 'alpine' => "const reason = prompt('Reason for rejecting this commission?'); if (reason && reason.trim()) { \$wire.reject({$id}, reason) }"];
    }
    if ($status === CommissionStatus::Submitted && $user?->can('rent out commission.ceo approve')) {
        $buttons[] = ['label' => 'CEO Approve', 'short' => 'CEO', 'icon' => 'fa-check', 'tone' => 'primary', 'click' => "approve({$id}, '" . ApproveAction::STAGE_CEO . "')", 'confirm' => 'Approve this commission as CEO?', 'primary' => true];
    }
    if ($status === CommissionStatus::CeoApproved && $user?->can('rent out commission.owner approve')) {
        $buttons[] = ['label' => 'Owner Approve', 'short' => 'Owner', 'icon' => 'fa-check', 'tone' => 'primary', 'click' => "approve({$id}, '" . ApproveAction::STAGE_OWNER . "')", 'confirm' => 'Approve this commission as Owner?', 'primary' => true];
    }
    if (in_array($status, [CommissionStatus::CeoApproved, CommissionStatus::OwnerApproved], true) && $user?->can('rent out commission.finance approve')) {
        $buttons[] = ['label' => 'Finance Approve', 'short' => 'Finance', 'icon' => 'fa-check', 'tone' => 'warning', 'click' => "approve({$id}, '" . ApproveAction::STAGE_FINANCE . "')", 'confirm' => 'Finance approve? This posts Dr Commission Expense / Cr Commission Payable.', 'primary' => $status === CommissionStatus::OwnerApproved];
    }
    if ($status === CommissionStatus::FinanceApproved) {
        if ($user?->can('rent out commission.finance approve')) {
            $buttons[] = ['label' => 'Reopen', 'short' => null, 'icon' => 'fa-undo', 'tone' => 'secondary', 'click' => "reopen({$id})", 'confirm' => 'Reopen this commission? The expense journal will be removed.'];
        }
        if ($user?->can('rent out commission.pay')) {
            $buttons[] = ['label' => 'Pay Commission', 'short' => 'Pay', 'icon' => 'fa-money', 'tone' => 'success', 'click' => "openPayModal({$id})", 'primary' => true];
        }
    }
    if ($status === CommissionStatus::Paid && $user?->can('rent out commission.pay')) {
        $buttons[] = ['label' => 'Cancel Payment', 'short' => null, 'icon' => 'fa-undo', 'tone' => 'danger', 'click' => "cancelPayment({$id})", 'confirm' => 'Cancel this commission payment? The payment journal will be removed.'];
    }
@endphp
@foreach ($buttons as $button)
    @php
        $class = match (true) {
            $labelled && ! empty($button['primary']) => "btn btn-sm btn-{$button['tone']}",
            $labelled => "btn btn-sm btn-outline-{$button['tone']}",
            $button['short'] !== null => "btn btn-sm btn-outline-{$button['tone']} py-0 px-1 me-1",
            default => "btn btn-sm btn-link text-{$button['tone']} p-0 me-2",
        };
    @endphp
    <button type="button" class="{{ $class }}" title="{{ $button['label'] }}"
        @if ($button['short'] !== null && ! $labelled) style="font-size: .65rem;" @endif
        @if (isset($button['alpine'])) x-on:click="{{ $button['alpine'] }}" @else wire:click="{{ $button['click'] }}" @endif
        @if (isset($button['confirm'])) wire:confirm="{{ $button['confirm'] }}" @endif>
        <i class="fa {{ $button['icon'] }} {{ $labelled || $button['short'] !== null ? 'me-1' : '' }}"></i>{{ $labelled ? $button['label'] : $button['short'] }}
    </button>
@endforeach
