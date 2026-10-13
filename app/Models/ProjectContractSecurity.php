<?php

namespace App\Models;

use App\Enums\ProjectContract\ApprovalStatus;
use App\Enums\ProjectContract\SecurityDirection;
use App\Enums\ProjectContract\SecurityForm;
use App\Enums\ProjectContract\SecurityPurpose;
use App\Enums\ProjectContract\SettlementType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Security lodged on a contract: a bank guarantee, cash or a cheque, usually
 * received from the vendor. Only cash posts a journal when lodged; it is
 * closed by approved settlements (return or forfeit), which may be partial.
 *
 * @property int $id
 * @property int $project_contract_id
 * @property SecurityDirection $direction
 * @property SecurityForm $form
 * @property SecurityPurpose $purpose
 * @property string $amount
 * @property int|null $payment_method_id
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property int|null $journal_id
 */
class ProjectContractSecurity extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    /** A guarantee that expires within this many days is flagged. */
    public const EXPIRY_WARNING_DAYS = 30;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'project_contract_id',
        'direction',
        'form',
        'purpose',
        'amount',
        'payment_method_id',
        'bank_name',
        'reference_no',
        'valid_from',
        'valid_until',
        'cheque_date',
        'received_date',
        'document_path',
        'renewed_from_id',
        'remarks',
        'journal_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'direction' => SecurityDirection::class,
            'form' => SecurityForm::class,
            'purpose' => SecurityPurpose::class,
            'amount' => 'decimal:2',
            'valid_from' => 'date:Y-m-d',
            'valid_until' => 'date:Y-m-d',
            'cheque_date' => 'date:Y-m-d',
            'received_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<ProjectContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProjectContract::class, 'project_contract_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }

    /** @return HasMany<ProjectContractSecuritySettlement, $this> */
    public function settlements(): HasMany
    {
        return $this->hasMany(ProjectContractSecuritySettlement::class);
    }

    /** Approved settlement total, by type. */
    public function settledAmount(?SettlementType $type = null): float
    {
        return (float) $this->settlements
            ->where('status', ApprovalStatus::Approved)
            ->when($type, fn ($settlements) => $settlements->where('type', $type))
            ->sum('amount');
    }

    /** What is still held: the amount less approved returns and forfeits. */
    public function heldAmount(): float
    {
        return round((float) $this->amount - $this->settledAmount(), 2);
    }

    /** Amount still free to settle: held, less settlements waiting on approval. */
    public function settleableAmount(): float
    {
        $pending = (float) $this->settlements->where('status', ApprovalStatus::PendingApproval)->sum('amount');

        return round($this->heldAmount() - $pending, 2);
    }

    /** held · partially_settled · returned · forfeited */
    public function state(): string
    {
        if ($this->heldAmount() > 0) {
            return $this->settledAmount() > 0 ? 'partially_settled' : 'held';
        }

        return $this->settledAmount(SettlementType::Forfeit) > 0 && $this->settledAmount(SettlementType::Return) <= 0 ? 'forfeited' : 'returned';
    }

    /** A held guarantee that lapses before the contract ends, or within the warning window. */
    public function expiresSoon(): bool
    {
        if (! $this->valid_until || $this->heldAmount() <= 0) {
            return false;
        }

        $contractEnd = $this->contract?->end_date;

        return $this->valid_until->lte(today()->addDays(self::EXPIRY_WARNING_DAYS))
            || ($contractEnd && $this->valid_until->lt($contractEnd));
    }
}
