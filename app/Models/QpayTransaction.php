<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One QPay EZ-Connect payment or refund for a student card (see the migration).
 */
class QpayTransaction extends Model
{
    use BelongsToTenant;

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_REFUND = 'refund';

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

    /** Statuses that may still receive a final answer from QPay. */
    public const STATUSES_AWAITING_RESULT = [self::STATUS_PENDING, self::STATUS_UNRESOLVED];

    protected $fillable = [
        'tenant_id',
        'type',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'Successful',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUND_PENDING => 'Refund pending',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_REVIEW => 'Needs review',
            self::STATUS_UNRESOLVED => 'Unresolved',
            default => 'Pending',
        };
    }
}
