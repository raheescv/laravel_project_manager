<?php

namespace App\Models;

use App\Support\Payment\MpgsSettings;
use App\Support\Payment\QPayRechargeExplanation;
use App\Support\Payment\QPaySettings;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One online payment or refund for a student card (see the migrations).
 *
 * Two gateways share the table, because everything around the payment — the
 * PUN the portal shows, the broken-transaction chase, the journal, the refund
 * rules — is the same: `qpay` is a Qatar debit card through QPay (QCB
 * EZ-Connect), `mpgs` a credit card through the Mastercard Gateway. The parent
 * picks one on the top-up page.
 */
class QpayTransaction extends Model
{
    use BelongsToTenant;

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

    /** Qatar debit card, QPay (QCB EZ-Connect). */
    public const GATEWAY_QPAY = 'qpay';

    /** Credit card, Mastercard Gateway (MPGS) Hosted Checkout. */
    public const GATEWAY_MPGS = 'mpgs';

    /** What the parent chose on the top-up page → the gateway that takes it. */
    public const METHODS = ['debit' => self::GATEWAY_QPAY, 'credit' => self::GATEWAY_MPGS];

    /** Sent to QPay; the outcome is not known yet. */
    public const STATUS_PENDING = 'pending';

    /** Paid, verified with QPay, and credited to the card through the journal. */
    public const STATUS_SUCCESS = 'success';

    /** QPay ended the payment without taking money. */
    public const STATUS_FAILED = 'failed';

    /** QPay reports the money taken but it could not be credited as-is (e.g. amount mismatch). A person must check. */
    public const STATUS_REVIEW = 'review';

    /** A refund QPay accepted but has not completed yet (code 5002). */
    public const STATUS_REFUND_PENDING = 'refund_pending';

    /** A payment that has been refunded in full. */
    public const STATUS_REFUNDED = 'refunded';

    /**
     * QPay never gave a final answer and the office released the payment so the
     * parent could use the card again. Not `failed`: failed means QPay confirmed
     * no money was taken, this means nobody knows. The row is still chased by
     * qpay:inquire-pending and is credited if the answer finally arrives.
     */
    public const STATUS_UNRESOLVED = 'unresolved';

    /**
     * The parent left the card payment page with Cancel (or it timed out) and the
     * gateway holds no payment. Not `failed` yet: the same checkout session can
     * still be paid for a while, so it is chased like a pending payment — and
     * credited if it turns out to have been paid — but it blocks nothing.
     */
    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses that may still receive a final answer from the gateway. */
    public const STATUSES_AWAITING_RESULT = [self::STATUS_PENDING, self::STATUS_UNRESOLVED, self::STATUS_CANCELLED];

    protected $fillable = [
        'tenant_id',
        'type',
        'gateway',
        'pun',
        'original_pun',
        'account_id',
        'guardian_id',
        'amount',
        'currency_code',
        'lang',
        'status',
        'gateway_status',
        'gateway_status_message',
        'confirmation_id',
        'masked_card',
        'card_brand',
        'funding_method',
        'request_date',
        'response_date',
        'completed_at',
        'last_inquired_at',
        'tampered_at',
        'journal_id',
        'payload',
        'failure_reason',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payload' => 'array',
        'completed_at' => 'datetime',
        'last_inquired_at' => 'datetime',
        'tampered_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Whether QPay could still tell us what happened to this payment — either it
     * has no outcome yet, or the office released it without one.
     */
    public function awaitsResult(): bool
    {
        return in_array($this->status, self::STATUSES_AWAITING_RESULT, true);
    }

    /**
     * A QPay payment still waiting for its answer holds the card; the office may
     * release it so the parent can top up again (see ReleaseAction).
     */
    public function isReleasable(): bool
    {
        return $this->isPending() && $this->type === self::TYPE_PAYMENT && ! $this->isCreditCard();
    }

    public function isCreditCard(): bool
    {
        return $this->gateway === self::GATEWAY_MPGS;
    }

    /** debit · credit — the choice the parent made on the top-up page. */
    public function method(): string
    {
        return $this->isCreditCard() ? 'credit' : 'debit';
    }

    public function methodLabel(): string
    {
        return $this->isCreditCard() ? 'Credit card' : 'Debit card';
    }

    /** The merchant set-up this payment belongs to: its bank account and the user journals are booked as. */
    public function gatewaySettings(): QPaySettings|MpgsSettings
    {
        return $this->isCreditCard() ? MpgsSettings::current() : QPaySettings::current();
    }

    /** The gateway by name, for staff: "QPay" / "MPGS". */
    public function gatewayLabel(): string
    {
        return $this->isCreditCard() ? 'MPGS' : 'QPay';
    }

    /** The card as the gateway reported it: "Visa ····0008", else the masked number. */
    public function cardLabel(): ?string
    {
        $last4 = $this->masked_card ? substr(preg_replace('/\D/', '', $this->masked_card), -4) : null;
        $brand = $this->card_brand && ! in_array($this->card_brand, ['UNKNOWN', 'LOCAL_BRAND_ONLY'], true)
            ? ucwords(strtolower(str_replace('_', ' ', $this->card_brand)))
            : null;

        return match (true) {
            $brand && $last4 => $brand.' ····'.$last4,
            default => $this->masked_card ?: $brand,
        };
    }

    /**
     * The PUNs of this payment and every refund of it (or, for a refund, of the
     * payment it gives back) — the whole conversation with the gateway.
     *
     * @return list<string>
     */
    public function relatedPuns(): array
    {
        $paymentPun = $this->type === self::TYPE_REFUND && $this->original_pun ? $this->original_pun : $this->pun;

        return self::query()
            ->where(fn ($query) => $query->where('pun', $paymentPun)->orWhere('original_pun', $paymentPun))
            ->pluck('pun')
            ->push($this->pun, $paymentPun)
            ->filter(fn ($pun): bool => filled($pun) && ctype_alnum((string) $pun))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Every request and response exchanged with the gateway for this payment and
     * its refunds, oldest first. Nothing links api_logs to a transaction, so a row
     * belongs here when its URL (MPGS order id) or body (QPay PUN) carries a PUN.
     *
     * @return Collection<int, ApiLog>
     */
    public function apiLogs(): Collection
    {
        $puns = $this->relatedPuns();
        if (! $puns) {
            return collect();
        }

        return ApiLog::query()
            ->where(function ($query) use ($puns): void {
                foreach ($puns as $pun) {
                    $query->orWhere('request', 'like', '%'.$pun.'%')->orWhere('endpoint', 'like', '%'.$pun.'%');
                }
            })
            ->where(fn ($query) => $query->where('service_name', 'like', 'QPay %')->orWhere('service_name', 'like', 'MPGS %'))
            ->orderBy('id')
            ->get();
    }

    /**
     * What happened to this payment, in words the office can act on.
     *
     * @param  Collection<int, ApiLog>|null  $logs
     * @return array{tone: string, headline: string, summary: string, facts: list<string>, timeline: list<array{at: string, label: string, ok: bool}>, next: ?string}
     */
    public function explanation(?Collection $logs = null): array
    {
        return QPayRechargeExplanation::from($this, $logs ?? $this->apiLogs());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'Successful',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUND_PENDING => 'Refund pending',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_REVIEW => 'Needs review',
            self::STATUS_UNRESOLVED => 'Unresolved',
            default => 'Pending',
        };
    }
}
