<?php

namespace App\Models;

use App\Enums\RentOut\CommissionStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * Commission owed to the agent who closed an agreement.
 *
 * It is a company expense, not something billed to the customer, so it never
 * touches rent_out_transactions (the customer's statement). Finance approval
 * posts Dr Commission Expense / Cr Commission Payable, and paying it posts
 * Dr Commission Payable / Cr the payment method.
 */
class RentOutCommission extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    /** Journal model key used for both the approval and payment journals. */
    public const JOURNAL_MODEL = 'RentOutCommission';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'agent_id',
        'date',
        'commission_type',
        'commission_percentage',
        'contract_amount',
        'amount',
        'status',
        'invoice_no',
        'remark',
        'submitted_by',
        'submitted_at',
        'ceo_approved_by',
        'ceo_approved_at',
        'owner_approved_by',
        'owner_approved_at',
        'finance_approved_by',
        'finance_approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'journal_id',
        'payment_account_id',
        'paid_date',
        'paid_by',
        'payment_journal_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'paid_date' => 'date',
            'submitted_at' => 'datetime',
            'ceo_approved_at' => 'datetime',
            'owner_approved_at' => 'datetime',
            'finance_approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'commission_percentage' => 'decimal:2',
            'contract_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'status' => CommissionStatus::class,
        ];
    }

    /**
     * @param  array<string, mixed>  $merge
     * @return array<string, mixed>
     */
    public static function rules(int $id = 0, array $merge = []): array
    {
        return array_merge([
            'tenant_id' => ['required'],
            'branch_id' => ['required'],
            'rent_out_id' => ['required'],
            'agent_id' => ['required'],
            'date' => ['required', 'date'],
            'commission_type' => ['required', 'in:'.self::TYPE_PERCENTAGE.','.self::TYPE_FIXED],
            'commission_percentage' => ['required_if:commission_type,'.self::TYPE_PERCENTAGE, 'numeric', 'min:0', 'max:100'],
            'contract_amount' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ], $merge);
    }

    /**
     * Percentage commission = contract amount × rate; a fixed commission is the
     * amount entered.
     */
    public static function calculate(string $type, float $contractAmount, float $percentage, float $fixedAmount): float
    {
        if ($type === self::TYPE_FIXED) {
            return round($fixedAmount, 2);
        }

        return round($contractAmount * $percentage / 100, 2);
    }

    public function typeLabel(): string
    {
        return $this->commission_type === self::TYPE_FIXED ? 'Fixed' : 'Percentage';
    }

    /** @return BelongsTo<RentOut, $this> */
    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    /** @return BelongsTo<User, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function ceoApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ceo_approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function ownerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function financeApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
