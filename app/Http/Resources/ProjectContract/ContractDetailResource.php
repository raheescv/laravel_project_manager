<?php

namespace App\Http\Resources\ProjectContract;

use App\Enums\ProjectContract\ApprovalStatus;
use App\Enums\ProjectContract\SecurityForm;
use App\Models\ProjectContract;
use App\Models\ProjectContractApproval;
use App\Models\ProjectContractExtension;
use App\Models\ProjectContractPayment;
use App\Models\ProjectContractSecurity;
use App\Models\ProjectContractSecuritySettlement;
use App\Models\User;
use App\Support\ProjectContract\ApprovalGate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Everything the contract view shows: header, totals, terms, payments,
 * extensions, security, documents, notes and the approval rounds — with what
 * the current user may act on.
 *
 * @mixin ProjectContract
 */
class ContractDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $amount = (float) $this->contract_amount;
        $posted = $this->payments->where('status', ProjectContractPayment::STATUS_POSTED);
        $paid = round((float) $posted->sum('amount'), 2);
        $scheduled = round((float) $this->terms->sum('amount'), 2);
        $nextDue = $this->terms->filter(fn ($term): bool => (float) $term->balance > 0)->sortBy('due_date')->first();
        $lastPayment = $posted->sortByDesc('date')->first();
        $firstExtension = $this->extensions->where('status', ApprovalStatus::Approved)->sortBy('id')->first();

        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'project_name' => $this->project_name,
            'vendor' => $this->vendor ? ['id' => $this->vendor->id, 'name' => $this->vendor->name, 'alias_name' => $this->vendor->alias_name, 'mobile' => $this->vendor->mobile] : null,
            'branch' => $this->branch?->name,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'no_of_terms' => $this->no_of_terms,
            'payment_frequency' => $this->payment_frequency->value,
            'payment_frequency_label' => $this->payment_frequency->label(),
            'contract_amount' => $amount,
            'original_amount' => $firstExtension ? (float) $firstExtension->old_amount : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'remark' => $this->remark,
            'created_by' => $this->creator?->name,
            'created_at' => $this->created_at?->toDateString(),
            'totals' => [
                'paid' => $paid,
                'balance' => round($amount - $paid, 2),
                'scheduled' => $scheduled,
                'scheduled_unpaid' => round($scheduled - (float) $this->terms->sum('paid'), 2),
                'unscheduled' => round($amount - $scheduled, 2),
                'terms_count' => $this->terms->count(),
                'terms_paid' => $this->terms->where('status.value', 'paid')->count(),
                'security_held' => round($this->securities->sum(fn (ProjectContractSecurity $security): float => $security->direction->value === 'received' ? $security->heldAmount() : 0), 2),
            ],
            'next_due' => $nextDue ? ['label' => $nextDue->label, 'date' => $nextDue->due_date->toDateString(), 'amount' => (float) $nextDue->balance, 'overdue' => $nextDue->isOverdue()] : null,
            'last_payment' => $lastPayment ? ['date' => $lastPayment->date->toDateString(), 'amount' => (float) $lastPayment->amount, 'method' => $lastPayment->paymentMethod?->name] : null,
            'terms' => $this->terms->map(fn ($term): array => [
                'id' => $term->id,
                'label' => $term->label,
                'due_date' => $term->due_date->toDateString(),
                'amount' => (float) $term->amount,
                'paid' => (float) $term->paid,
                'balance' => (float) $term->balance,
                'status' => $term->status->value,
                'overdue' => $term->isOverdue(),
                'remarks' => $term->remarks,
                'has_payments' => $term->allocations_count > 0,
            ])->values(),
            'payments' => $this->payments->sortByDesc('date')->map(fn (ProjectContractPayment $payment): array => [
                'id' => $payment->id,
                'voucher_no' => $payment->voucher_no,
                'date' => $payment->date->toDateString(),
                'amount' => (float) $payment->amount,
                'method' => $payment->paymentMethod?->name,
                'cheque_no' => $payment->cheque_no,
                'bank_name' => $payment->bank_name,
                'reference' => $payment->reference,
                'remarks' => $payment->remarks,
                'status' => $payment->status,
                'journal_id' => $payment->journal_id,
                'reversal_reason' => $payment->reversal_reason,
                'terms' => $payment->allocations->map(fn ($allocation): string => $allocation->term?->label ?? '')->filter()->values(),
                'created_by' => $payment->creator?->name,
            ])->values(),
            'extensions' => $this->extensions->sortByDesc('id')->map(fn (ProjectContractExtension $extension): array => [
                'id' => $extension->id,
                'old_end_date' => $extension->old_end_date->toDateString(),
                'new_end_date' => $extension->new_end_date->toDateString(),
                'old_amount' => (float) $extension->old_amount,
                'new_amount' => $extension->newAmount(),
                'old_no_of_terms' => $extension->old_no_of_terms,
                'new_no_of_terms' => $extension->old_no_of_terms + $extension->additional_terms,
                'reason' => $extension->reason,
                'document_url' => $extension->document_path ? Storage::disk('public')->url($extension->document_path) : null,
                'status' => $extension->status->value,
                'created_at' => $extension->created_at?->toDateString(),
                'created_by' => $extension->creator?->name,
                'approval' => $this->approval($extension, $user),
            ])->values(),
            'securities' => $this->securities->sortByDesc('id')->map(fn (ProjectContractSecurity $security): array => [
                'id' => $security->id,
                'direction' => $security->direction->value,
                'direction_label' => $security->direction->label(),
                'form' => $security->form->value,
                'form_label' => $security->form->label(),
                'purpose' => $security->purpose->value,
                'purpose_label' => $security->purpose->label(),
                'amount' => (float) $security->amount,
                'held' => $security->heldAmount(),
                'settleable' => $security->settleableAmount(),
                'state' => $security->state(),
                'method' => $security->paymentMethod?->name,
                'bank_name' => $security->bank_name,
                'reference_no' => $security->reference_no,
                'valid_from' => $security->valid_from?->toDateString(),
                'valid_until' => $security->valid_until?->toDateString(),
                'cheque_date' => $security->cheque_date?->toDateString(),
                'received_date' => $security->received_date?->toDateString(),
                'expires_soon' => $security->expiresSoon(),
                'posts_journal' => $security->form === SecurityForm::Cash,
                'journal_id' => $security->journal_id,
                'document_url' => $security->document_path ? Storage::disk('public')->url($security->document_path) : null,
                'settlements' => $security->settlements->sortBy('id')->map(fn (ProjectContractSecuritySettlement $settlement): array => [
                    'id' => $settlement->id,
                    'type' => $settlement->type->value,
                    'type_label' => $settlement->type->label(),
                    'amount' => (float) $settlement->amount,
                    'date' => $settlement->date->toDateString(),
                    'reason' => $settlement->reason,
                    'status' => $settlement->status->value,
                    'approval' => $this->approval($settlement, $user),
                ])->values(),
            ])->values(),
            'documents' => $this->documents->sortByDesc('id')->map(fn ($document): array => [
                'id' => $document->id,
                'name' => $document->name,
                'document_type' => $document->document_type,
                'url' => $document->url(),
                'mime_type' => $document->mime_type,
                'size' => $document->size,
                'created_at' => $document->created_at?->toDateString(),
                'created_by' => $document->creator?->name,
            ])->values(),
            'notes' => $this->notes->sortByDesc('id')->map(fn ($note): array => [
                'id' => $note->id,
                'note' => $note->note,
                'created_at' => $note->created_at?->toDateTimeString(),
                'created_by' => $note->creator?->name,
                'mine' => $note->created_by === $user->id,
            ])->values(),
            'approval' => $this->approval($this->resource, $user),
        ];
    }

    /**
     * The latest approval round for one submission, and whether this user may act now.
     *
     * @return array<string, mixed>|null
     */
    private function approval(Model $approvable, User $user): ?array
    {
        $round = ApprovalGate::round($approvable);
        if ($round->isEmpty()) {
            return null;
        }

        $current = $round->firstWhere('status', ProjectContractApproval::PENDING);

        return [
            'levels' => $round->map(fn (ProjectContractApproval $approval): array => [
                'level' => $approval->level,
                'role' => $approval->approver_role,
                'status' => $approval->status,
                'acted_by' => $approval->actor?->name,
                'acted_at' => $approval->acted_at?->toDateTimeString(),
                'comment' => $approval->comment,
            ])->values(),
            'current_level' => $current?->level,
            'level_count' => $round->count(),
            'can_act' => $current !== null && ApprovalGate::refusal($user, $current) === null,
            'refusal' => $current ? ApprovalGate::refusal($user, $current) : null,
        ];
    }
}
