<?php

namespace App\Models;

use App\Enums\ProjectContract\ApprovalStatus;
use App\Enums\ProjectContract\SettlementType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Returning or forfeiting (part of) a security. It waits on the approval
 * levels and posts its journal only once approved.
 *
 * @property int $id
 * @property int $project_contract_security_id
 * @property SettlementType $type
 * @property string $amount
 * @property \Illuminate\Support\Carbon $date
 * @property int|null $payment_method_id
 * @property ApprovalStatus $status
 * @property int|null $journal_id
 */
class ProjectContractSecuritySettlement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_contract_security_id',
        'type',
        'amount',
        'date',
        'payment_method_id',
        'reason',
        'status',
        'journal_id',
        'applied_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettlementType::class,
            'status' => ApprovalStatus::class,
            'amount' => 'decimal:2',
            'date' => 'date:Y-m-d',
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProjectContractSecurity, $this> */
    public function security(): BelongsTo
    {
        return $this->belongsTo(ProjectContractSecurity::class, 'project_contract_security_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_method_id');
    }
}
