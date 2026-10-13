<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How much of one payment went to one payment term.
 *
 * @property int $id
 * @property int $project_contract_payment_id
 * @property int $project_contract_payment_term_id
 * @property string $amount
 */
class ProjectContractPaymentAllocation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_contract_payment_id',
        'project_contract_payment_term_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ProjectContractPayment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(ProjectContractPayment::class, 'project_contract_payment_id');
    }

    /** @return BelongsTo<ProjectContractPaymentTerm, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(ProjectContractPaymentTerm::class, 'project_contract_payment_term_id');
    }
}
