<?php

namespace App\Models;

use App\Models\Scopes\AssignedBranchScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A storefront bag on its way through Tap Payments.
 *
 * Created when the customer presses Pay; settled by App\Actions\V1\Storefront\SyncCheckoutAction,
 * which asks Tap for the charge and records the completed Sale once it is captured.
 */
class StorefrontCheckout extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;

    /** Customer sent to Tap; nothing has settled yet. */
    public const STATUS_PENDING = 'pending';

    /** Charge captured and the completed sale recorded. */
    public const STATUS_PAID = 'paid';

    /** Tap ended the charge without taking money (declined, abandoned, cancelled…). */
    public const STATUS_FAILED = 'failed';

    /** Money captured but no sale could be recorded — a person has to resolve it. */
    public const STATUS_REVIEW = 'review';

    protected $fillable = [
        'tenant_id',
        'reference',
        'branch_id',
        'sale_id',
        'fulfilment',
        'customer_name',
        'customer_mobile',
        'customer_email',
        'address',
        'items',
        'amount',
        'currency',
        'gateway',
        'gateway_charge_id',
        'gateway_status',
        'gateway_response',
        'status',
        'failure_reason',
        'paid_at',
    ];

    protected $casts = [
        'items' => 'array',
        'gateway_response' => 'array',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /** The raw Tap payload is re-written on every status check; auditing it is noise. */
    protected $auditExclude = ['gateway_response'];

    protected static function booted()
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
