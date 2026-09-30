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
        'zone_number',
        'street_number',
        'building_number',
        'city',
        'latitude',
        'longitude',
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
        'latitude' => 'float',
        'longitude' => 'float',
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

    public function isDelivery(): bool
    {
        return $this->fulfilment === 'delivery';
    }

    public function hasMapPin(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * The transaction facts worth showing staff, read from the stored Tap charge.
     * Every key is optional in Tap's payload, so a missing one comes back null.
     *
     * @return array{method: ?string, payment_type: ?string, card_number: ?string, payment_reference: ?string, acquirer_reference: ?string, gateway_reference: ?string, receipt_no: ?string, response_code: ?string, response_message: ?string}
     */
    public function paymentDetails(): array
    {
        $charge = $this->gateway_response ?? [];
        $firstSix = data_get($charge, 'card.first_six');
        $lastFour = data_get($charge, 'card.last_four');

        return [
            'method' => data_get($charge, 'source.payment_method') ?: data_get($charge, 'card.brand') ?: data_get($charge, 'card.scheme'),
            'payment_type' => data_get($charge, 'source.payment_type'),
            'card_number' => $lastFour ? ($firstSix ? $firstSix.' •• ' : '•••• ').$lastFour : null,
            'payment_reference' => data_get($charge, 'reference.payment'),
            'acquirer_reference' => data_get($charge, 'reference.acquirer'),
            'gateway_reference' => data_get($charge, 'reference.gateway'),
            'receipt_no' => data_get($charge, 'receipt.id'),
            'response_code' => data_get($charge, 'response.code'),
            'response_message' => data_get($charge, 'response.message'),
        ];
    }

    /** Google Maps, keyless embed of the delivery pin. */
    public function mapEmbedUrl(): ?string
    {
        return $this->hasMapPin()
            ? "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}&z=16&output=embed"
            : null;
    }

    /** Opens the pin, or searches the typed address when the customer picked no pin. */
    public function mapUrl(): ?string
    {
        if ($this->hasMapPin()) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }

        return $this->address ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->address) : null;
    }

    public function directionsUrl(): ?string
    {
        return $this->hasMapPin()
            ? "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}"
            : null;
    }
}
