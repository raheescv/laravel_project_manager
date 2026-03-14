<?php

namespace App\Models;

use App\Enums\RentOut\AgreementType;
use App\Enums\RentOut\PaymentMode;
use App\Enums\RentOut\RentOutBookingStatus;
use App\Enums\RentOut\RentOutStatus;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOut extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'property_id',
        'property_building_id',
        'property_type_id',
        'property_group_id',
        'account_id',
        'salesman_id',
        'agreement_type',
        'booking_type',
        'status',
        'booking_status',
        'start_date',
        'end_date',
        'vacate_date',
        'rent',
        'no_of_terms',
        'payment_frequency',
        'discount',
        'free_month',
        'total',
        'collection_starting_day',
        'collection_payment_mode',
        'collection_bank_name',
        'collection_cheque_no',
        'management_fee',
        'management_fee_payment_method_id',
        'management_fee_remarks',
        'down_payment',
        'down_payment_payment_method_id',
        'down_payment_remarks',
        'include_electricity_water',
        'include_ac',
        'include_wifi',
        'remark',
        'cancellation_policy_ar',
        'cancellation_policy_en',
        'payment_terms_ar',
        'payment_terms_en',
        'payment_terms_extended_ar',
        'payment_terms_extended_en',
        'mandatory_documents',
        'reservation_fees_disclaimer_en',
        'reservation_fees_disclaimer_ar',
        'payment_term_rent',
        'payment_term_discount',
        'payment_term_total',
        'total_paid',
        'total_current_rent',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'financial_approved_by',
        'financial_approved_at',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'agreement_type' => AgreementType::class,
        'status' => RentOutStatus::class,
        'booking_status' => RentOutBookingStatus::class,
        'collection_payment_mode' => PaymentMode::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'vacate_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'financial_approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'reservation_fees_disclaimer_en' => 'array',
        'reservation_fees_disclaimer_ar' => 'array',
        'rent' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'management_fee' => 'decimal:2',
        'down_payment' => 'decimal:2',
    ];

    public static function rules($id = 0, array $merge = []): array
    {
        return array_merge([
            'account_id' => 'required',
            'property_id' => 'required',
            'booking_type' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'collection_starting_day' => 'required|numeric|min:1|max:31',
            'collection_payment_mode' => 'required',
        ], $merge);
    }

    public static array $bookingRules = [
        'account_id' => 'required',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'rent' => 'required|numeric|min:0',
        'total' => 'required|numeric|min:0',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->total = $model->rent * $model->totalStay() - $model->discount;
            if ($model->property_id) {
                $property = $model->property;
                if ($property) {
                    $model->property_type_id = $property->property_type_id;
                    $model->property_building_id = $property->property_building_id;
                    $building = $property->building;
                    if ($building) {
                        $model->property_group_id = $building->property_group_id;
                    }
                }
            }
        });
    }

    // Scopes
    public function scopeOccupied($query)
    {
        return $query->where('status', RentOutStatus::Occupied);
    }

    public function scopeVacated($query)
    {
        return $query->where('status', RentOutStatus::Vacated);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', RentOutStatus::Expired);
    }

    public function scopeBooked($query)
    {
        return $query->where('status', RentOutStatus::Booked);
    }

    public function scopeRental($query)
    {
        return $query->where('agreement_type', AgreementType::Rental);
    }

    public function scopeLease($query)
    {
        return $query->where('agreement_type', AgreementType::Lease);
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class)->withTrashed();
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(PropertyBuilding::class, 'property_building_id')->withTrashed();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PropertyGroup::class, 'property_group_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    public function managementFeePaymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'management_fee_payment_method_id');
    }

    public function downPaymentPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'down_payment_payment_method_id');
    }

    public function securities(): HasMany
    {
        return $this->hasMany(RentOutSecurity::class);
    }

    public function extends(): HasMany
    {
        return $this->hasMany(RentOutExtend::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(RentOutCheque::class);
    }

    public function utilityTerms(): HasMany
    {
        return $this->hasMany(RentOutUtilityTerm::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(RentOutService::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RentOutNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RentOutDocument::class);
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(RentOutPaymentTerm::class);
    }

    public function rentOutPayments(): HasMany
    {
        return $this->hasMany(RentOutPayment::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'model_id')->where('model', self::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function financialApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'financial_approved_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Helper Methods
    public function getAgreementNoAttribute(): string
    {
        $yearCode = date('y', strtotime($this->start_date));
        $id = str_pad($this->id, 3, '0', STR_PAD_LEFT);

        if ($this->agreement_type === AgreementType::Rental) {
            return "BASL/{$yearCode}-{$id}";
        }

        return "BASL/L/{$yearCode}-{$id}";
    }

    public function getReferenceNoAttribute(): string
    {
        $yearCode = date('y', strtotime($this->start_date));
        $id = str_pad($this->id, 3, '0', STR_PAD_LEFT);

        if ($this->agreement_type === AgreementType::Rental) {
            return "BAS/L/{$yearCode} - {$id}";
        }

        return "BAS/S/{$yearCode} - {$id}";
    }

    public function daysUntil($date): int
    {
        return Carbon::now()->diffInDays($date, false);
    }

    public function vacateDaysLeft(): string
    {
        return $this->vacate_date ? (string) $this->daysUntil($this->vacate_date) : '';
    }

    public function totalStay(): int
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        $months = ($end->year - $start->year) * 12 + ($end->month - $start->month);
        if ($end->day >= $start->day) {
            $months++;
        }

        return max($months, 0);
    }

    public function remaining(): int
    {
        $months = date('m', strtotime($this->start_date)) - date('m') + 1
            + 12 * (date('Y', strtotime($this->end_date)) - date('Y'));

        return max($months, 0);
    }

    public static function getOverlapping($propertyId, $startDate, $endDate, $excludeId = null)
    {
        return static::where('property_id', $propertyId)
            ->whereIn('status', [
                RentOutStatus::Occupied,
                RentOutStatus::Vacated,
                RentOutStatus::Expired,
            ])
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->when($excludeId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->with('customer')
            ->get();
    }
}
