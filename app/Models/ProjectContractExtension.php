<?php

namespace App\Models;

use App\Enums\ProjectContract\ApprovalStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request to move the contract's end date and raise its amount or planned
 * terms. Its changes reach the contract only once every approval level agrees;
 * the old values stay here as history.
 *
 * @property int $id
 * @property int $project_contract_id
 * @property \Illuminate\Support\Carbon $old_end_date
 * @property \Illuminate\Support\Carbon $new_end_date
 * @property string $old_amount
 * @property string $additional_amount
 * @property int $old_no_of_terms
 * @property int $additional_terms
 * @property ApprovalStatus $status
 */
class ProjectContractExtension extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'project_contract_id',
        'old_end_date',
        'new_end_date',
        'old_amount',
        'additional_amount',
        'old_no_of_terms',
        'additional_terms',
        'reason',
        'document_path',
        'status',
        'applied_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'old_end_date' => 'date:Y-m-d',
            'new_end_date' => 'date:Y-m-d',
            'old_amount' => 'decimal:2',
            'additional_amount' => 'decimal:2',
            'old_no_of_terms' => 'integer',
            'additional_terms' => 'integer',
            'status' => ApprovalStatus::class,
            'applied_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProjectContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProjectContract::class, 'project_contract_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The amount the contract would have after this extension; the approval levels are chosen by it. */
    public function newAmount(): float
    {
        return round((float) $this->old_amount + (float) $this->additional_amount, 2);
    }
}
