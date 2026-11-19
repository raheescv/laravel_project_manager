<?php

namespace App\Models;

use App\Traits\BelongsToTenantThroughParent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHasBranch extends Model
{
    use BelongsToTenantThroughParent;
    use HasFactory;

    public function tenantParentRelation(): string
    {
        return 'user';
    }

    protected $fillable = [
        'user_id',
        'branch_id',
    ];

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
