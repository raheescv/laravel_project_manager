<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A canteen pre-order a parent set up for their child (see the migration).
 * Items only — the card is charged by the sale the till rings up at the tap.
 */
class StudentPreOrder extends Model
{
    use BelongsToTenant;

    public const TYPE_DAY = 'day';

    public const TYPE_WEEKLY = 'weekly';

    public const STATUS_ACTIVE = 'active';

    /** A weekly order the parent has switched off for now. */
    public const STATUS_PAUSED = 'paused';

    /** A day order meaning "nothing this day" — it silences the weekly order. */
    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_CANCELLED = 'cancelled';

    /** Most lines and the most of one item a parent can order. */
    public const MAX_LINES = 20;

    public const MAX_QUANTITY = 10;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'guardian_id',
        'type',
        'date',
        'weekdays',
        'status',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'weekdays' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudentPreOrderItem::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(StudentPreOrderCollection::class);
    }

    public function isWeekly(): bool
    {
        return $this->type === self::TYPE_WEEKLY;
    }
}
