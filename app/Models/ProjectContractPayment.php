<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A payment made to the contract's vendor. It posts Dr Vendor / Cr payment
 * method and is spread over one or more payment terms through allocations.
 * A posted payment is reversed (contra journal), never deleted.
 *
 * @property int $id
 * @property int $project_contract_id
 * @property string $voucher_no
 * @property \Illuminate\Support\Carbon $date
 * @property string $amount
 * @property int $payment_method_id
 * @property string $status
 * @property int|null $journal_id
 */
class ProjectContractPayment extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'project_contract_id',
        'voucher_no',
        'date',
        'amount',
        'payment_method_id',
        'cheque_no',
        'cheque_date',
        'bank_name',
        'reference',
        'remarks',
        'status',
        'journal_id',
        'reversal_journal_id',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'cheque_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public static function nextVoucherNo(): string
    {
        $number = (int) static::withTrashed()->count() + 1;
        do {
            $voucher = 'PCP-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (static::withTrashed()->where('voucher_no', $voucher)->exists());

        return $voucher;
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ProjectContractPaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(ProjectContractPaymentAllocation::class);
    }
}
