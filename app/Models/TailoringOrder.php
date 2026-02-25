<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class TailoringOrder extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'order_no',
        'branch_id',
        'sale_day_session_id',
        'account_id',
        'customer_name',
        'customer_mobile',
        'salesman_id',
        'order_date',
        'delivery_date',
        'gross_amount',
        'item_discount',
        'tax_amount',
        'stitch_amount',

        'other_discount',
        'round_off',

        'paid',

        'payment_method_ids',
        'payment_method_name',
        'status',
        'delivery_status',
        'notes',
        'rack_id',
        'cutter_id',
        'cutter_rating',
        'completion_date',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'order_date' => 'date:Y-m-d',
        'delivery_date' => 'date:Y-m-d',
        'completion_date' => 'date:Y-m-d',
        'gross_amount' => 'decimal:2',
        'item_discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'stitch_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'other_discount' => 'decimal:2',
        'round_off' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'cutter_rating' => 'integer',
    ];

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'order_no' => ['required', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
            'order_date' => ['required', 'date'],
        ], $merge);
    }

    protected static function booted()
    {
        static::creating(function ($order): void {
            if (empty($order->order_no)) {
                $order->order_no = self::generateOrderNo();
            }
            if (empty($order->created_by)) {
                $order->created_by = Auth::id();
            }

            if ($order->branch_id) {
                if (empty($order->sale_day_session_id)) {
                    $openSession = SaleDaySession::getOpenSessionForBranch($order->branch_id);
                    if ($openSession) {
                        $order->sale_day_session_id = $openSession->id;
                        $order->order_date = $openSession->opened_at->format('Y-m-d');
                    }
                } else {
                    $session = SaleDaySession::find($order->sale_day_session_id);
                    if (! $session || $session->status !== 'open' || $session->branch_id !== (int) $order->branch_id) {
                        throw ValidationException::withMessages([
                            'sale_day_session_id' => 'Invalid or closed day session provided.',
                        ]);
                    }
                }
            }
        });

        static::updating(function ($order): void {
            if (empty($order->updated_by)) {
                $order->updated_by = Auth::id();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function saleDaySession(): BelongsTo
    {
        return $this->belongsTo(SaleDaySession::class, 'sale_day_session_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class, 'rack_id');
    }

    public function cutter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cutter_id');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TailoringOrderItem::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(TailoringOrderMeasurement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TailoringPayment::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', 'TailoringOrder');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('order_date', $date);
    }

    public function scopeByCustomer($query, $customerName)
    {
        return $query->where('customer_name', 'like', "%{$customerName}%");
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? '', function ($q, $search) {
                $search = trim($search);

                return $q->where(function ($q) use ($search): void {
                    $q->where('tailoring_orders.order_no', 'like', "%{$search}%")
                        ->orWhere('tailoring_orders.customer_name', 'like', "%{$search}%")
                        ->orWhere('tailoring_orders.customer_mobile', 'like', "%{$search}%")
                        ->orWhereHas('account', function ($subQ) use ($search) {
                            $subQ->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? '', fn ($q, $value) => $q->where('tailoring_orders.status', $value))
            ->when($filters['customer_id'] ?? '', fn ($q, $value) => $q->where('tailoring_orders.account_id', $value))
            ->when($filters['branch_id'] ?? '', fn ($q, $value) => $q->where('tailoring_orders.branch_id', $value))
            ->when($filters['sale_day_session_id'] ?? '', fn ($q, $value) => $q->where('tailoring_orders.sale_day_session_id', $value))
            ->when($filters['payment_status'] ?? '', function ($q, $value) {
                if ($value === 'paid') {
                    return $q->where('tailoring_orders.balance', '<=', 0);
                } elseif ($value === 'balance') {
                    return $q->where('tailoring_orders.balance', '>', 0);
                }

                return $q;
            })
            ->when(($filters['from_date'] ?? '') && ($filters['date_type'] ?? ''), function ($q) use ($filters) {
                $dateField = ($filters['date_type'] ?? 'order_date') === 'delivery_date' ? 'delivery_date' : 'order_date';

                return $q->whereDate("tailoring_orders.{$dateField}", '>=', date('Y-m-d', strtotime($filters['from_date'])));
            })
            ->when(($filters['to_date'] ?? '') && ($filters['date_type'] ?? ''), function ($q) use ($filters) {
                $dateField = ($filters['date_type'] ?? 'order_date') === 'delivery_date' ? 'delivery_date' : 'order_date';

                return $q->whereDate("tailoring_orders.{$dateField}", '<=', date('Y-m-d', strtotime($filters['to_date'])));
            });
    }

    // Methods
    /**
     * Recalculate and set order-level amounts from items and payments.
     * total, grand_total, balance are computed by the database (storedAs) and must not be set here.
     */
    public function calculateTotals()
    {
        $this->gross_amount = $this->items->sum('gross_amount');
        $this->item_discount = $this->items->sum('discount');
        $this->tax_amount = $this->items->sum('tax_amount');
        $this->stitch_amount = $this->items->sum('total_stitch_amount');
        $this->paid = $this->payments->sum('amount');
    }

    public function updateTotals()
    {
        $this->calculateTotals();
        $this->save();
    }

    public function updatePaymentMethods()
    {
        $paymentMethodIds = $this->payments->pluck('payment_method_id')->toArray();
        $paymentMethodNames = Account::whereIn('id', $paymentMethodIds)->pluck('name')->toArray();

        $this->payment_method_ids = implode(',', $paymentMethodIds);
        $this->payment_method_name = implode(', ', $paymentMethodNames);
        $this->paid = $this->payments->sum('amount');
        $this->save();
    }

    public function appendMeasurementsToItems()
    {
        $this->loadMissing(['measurements.category.activeMeasurements', 'items.category.activeMeasurements']);

        // Key measurements by category + model + model_type so each item gets the correct measurement
        $measurementsByKey = $this->measurements->keyBy(function ($m) {
            return $m->tailoring_category_id.'-'.($m->tailoring_category_model_id ?? 'null').'-'.($m->tailoring_category_model_type_id ?? 'null');
        });

        foreach ($this->items as $item) {
            if (! $item->tailoring_category_id) {
                continue;
            }

            $itemKey = $item->tailoring_category_id.'-'.($item->tailoring_category_model_id ?? 'null').'-'.($item->tailoring_category_model_type_id ?? 'null');
            $meas = $measurementsByKey->get($itemKey);

            if (! $meas) {
                continue;
            }

            // Set explicit columns
            $item->setAttribute('tailoring_category_model_id', $meas->tailoring_category_model_id);
            $item->setAttribute('tailoring_category_model_type_id', $meas->tailoring_category_model_type_id);
            $item->setAttribute('tailoring_notes', $meas->tailoring_notes);

            $data = $meas->data;
            if (! empty($data) && (is_array($data) || is_object($data))) {
                $fieldKeys = $meas->category->activeMeasurements->pluck('field_key')->toArray();

                foreach ($data as $key => $value) {
                    if ($value === null) {
                        continue;
                    }

                    $finalKey = (string) $key;
                    // If key is numeric, try to find the actual field key by index
                    if (is_numeric($key) && isset($fieldKeys[(int) $key])) {
                        $finalKey = $fieldKeys[(int) $key];
                    }

                    $item->setAttribute($finalKey, $value);
                }
            }
        }

        return $this;
    }
}
