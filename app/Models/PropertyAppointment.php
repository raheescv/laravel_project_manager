<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class PropertyAppointment extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    /** Statuses that occupy a slot on the salesman's calendar. */
    public const HOLDING_STATUSES = ['scheduled', 'completed'];

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'reference_no',
        'rent_out_id',
        'account_id',
        'salesman_id',
        'scheduled_at',
        'status',
        'token',
        'token_expires_at',
        'link_sent_at',
        'link_opened_at',
        'link_opened_count',
        'booked_at',
        'booked_by',
        'reminder_sent_at',
        'customer_timezone',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // NOTE: active_slot_key is a STORED generated column backing the unique
    // index that makes double-appointment impossible. It is deliberately absent
    // from $fillable — the database owns it and writing to it errors.
    protected $casts = [
        'scheduled_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'link_sent_at' => 'datetime',
        'link_opened_at' => 'datetime',
        'booked_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'link_opened_count' => 'integer',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        return array_merge([
            'rent_out_id' => 'required|exists:rent_outs,id',
            'account_id' => 'required|exists:accounts,id',
            'salesman_id' => 'required|exists:users,id',
            'status' => 'required|in:awaiting,scheduled,completed,cancelled,no_show',
            'scheduled_at' => 'nullable|date',
        ], $merge);
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany(EmailLog::class, 'related');
    }

    public function scopeHoldingSlot(Builder $query): Builder
    {
        return $query->whereIn('status', self::HOLDING_STATUSES)->whereNotNull('scheduled_at');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')->where('scheduled_at', '>=', now());
    }

    /**
     * Statuses that permanently close the customer's link.
     *
     * Only a completed visit does. A no-show or a cancellation is precisely
     * when the customer SHOULD be able to pick a new slot themselves instead
     * of having to phone in — and active_slot_key excludes both, so the time
     * they lost is already free for someone else.
     *
     * Staff can still close a link deliberately with RevokeLinkAction, which
     * expires the token.
     */
    public const CLOSED_STATUSES = ['completed'];

    public function isLinkUsable(): bool
    {
        if (in_array($this->status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return blank($this->token_expires_at) || $this->token_expires_at->isFuture();
    }

    /** Whether the customer is being offered a slot grid right now. */
    public function isBookable(): bool
    {
        return $this->isLinkUsable() && $this->status !== 'scheduled';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'awaiting' => 'Awaiting customer',
            'scheduled' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No-show',
            default => (string) $this->status,
        };
    }
}
