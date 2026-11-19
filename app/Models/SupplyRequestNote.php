<?php

namespace App\Models;

use App\Traits\BelongsToTenantThroughParent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyRequestNote extends Model
{
    use BelongsToTenantThroughParent;

    public function tenantParentRelation(): string
    {
        return 'supplyRequest';
    }

    protected $fillable = [
        'supply_request_id',
        'note',
        'created_by',
    ];

    /** @return BelongsTo<SupplyRequest, $this> */
    public function supplyRequest(): BelongsTo
    {
        return $this->belongsTo(SupplyRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
