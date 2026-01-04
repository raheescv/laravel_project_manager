<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniqueNoCounter extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'year', 'branch_code', 'segment', 'number'];

    public $timestamps = false;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
