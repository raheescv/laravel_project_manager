<?php

namespace App\Models;

use App\Enums\ProjectContract\TermStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One scheduled payment on a project contract, added by hand. `paid` and
 * `balance` follow the posted payment allocations; `status` follows from them.
 *
 * @property int $id
 * @property int $project_contract_id
 * @property string $label
 * @property \Illuminate\Support\Carbon $due_date
 * @property string $amount
 * @property string $paid
 * @property string $balance
 * @property TermStatus $status
 */
class ProjectContractPaymentTerm extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\ProjectContractPaymentTermFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'project_contract_id',
        'label',
        'due_date',
        'amount',
        'paid',
        'balance',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => TermStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $term): void {
            $term->paid = round((float) $term->paid, 2);
            $term->balance = round((float) $term->amount - (float) $term->paid, 2);
            $term->status = TermStatus::fromAmounts((float) $term->amount, (float) $term->paid);
        });
    }

    /** @return BelongsTo<ProjectContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProjectContract::class, 'project_contract_id');
    }

    /** @return HasMany<ProjectContractPaymentAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(ProjectContractPaymentAllocation::class);
    }

    public function isOverdue(): bool
    {
        return (float) $this->balance > 0 && $this->due_date->lt(today());
    }

    /** Recompute `paid` from the allocations of posted payments. */
    public function refreshPaid(): void
    {
        $this->paid = (float) $this->allocations()
            ->whereHas('payment', fn ($query) => $query->where('status', ProjectContractPayment::STATUS_POSTED))
            ->sum('amount');
        $this->save();
    }
}
