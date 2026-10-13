<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One approval level for one submission: the contract itself, an extension
 * or a security settlement. Rows are created together when something is
 * submitted, and acted on in level order. Each resubmission opens a new
 * `round`; earlier rounds stay as history.
 *
 * @property int $id
 * @property int $project_contract_id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property int $round
 * @property int $level
 * @property string $approver_role
 * @property string $status
 * @property int|null $acted_by
 * @property \Illuminate\Support\Carbon|null $acted_at
 * @property string|null $comment
 * @property int $submitted_by
 */
class ProjectContractApproval extends Model
{
    use BelongsToTenant;

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    /** Closed rows from an earlier, rejected round; kept as history. */
    public const SUPERSEDED = 'superseded';

    protected $fillable = [
        'tenant_id',
        'project_contract_id',
        'approvable_type',
        'approvable_id',
        'round',
        'level',
        'approver_role',
        'status',
        'acted_by',
        'acted_at',
        'comment',
        'submitted_by',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'level' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProjectContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(ProjectContract::class, 'project_contract_id');
    }

    /** @return MorphTo<Model, $this> */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
