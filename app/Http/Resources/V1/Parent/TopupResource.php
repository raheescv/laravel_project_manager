<?php

namespace App\Http\Resources\V1\Parent;

use App\Actions\Student\GetBalanceAction;
use App\Models\QpayTransaction;
use App\Services\Payment\QPayClient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A top-up as the parent sees it — what QPay certification asks the merchant's
 * result page to show: reference (PUN), amount, status, date and time — plus how
 * it was paid (debit card through QPay, credit card through the Mastercard Gateway).
 *
 * @mixin \App\Models\QpayTransaction
 */
class TopupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $success = $this->status === QpayTransaction::STATUS_SUCCESS;

        return [
            'pun' => $this->pun,
            'student' => ['account_id' => $this->account_id, 'name' => $this->account?->name],
            'amount' => round((float) $this->amount, 2),
            'currency' => $this->currency_code === QPayClient::CURRENCY_QAR ? 'QAR' : $this->currency_code,
            // debit · credit
            'method' => $this->method(),
            'method_label' => $this->methodLabel(),
            // "Visa ····0008" once the gateway says which card was used.
            'card' => $this->cardLabel(),
            // pending · success · failed · cancelled · review · refund_pending · refunded · unresolved
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            // The gateway's own words for a failed payment. Review reasons are internal and stay with the school.
            'message' => $this->status === QpayTransaction::STATUS_FAILED ? $this->gateway_status_message : null,
            // QPay's confirmation ID, or the card receipt (RRN) for a credit card.
            'confirmation_id' => $this->confirmation_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'balance' => $success ? (new GetBalanceAction())->execute($this->account_id) : null,
        ];
    }
}
