<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniqueNoCounter extends Model
{
    protected $fillable = ['tenant_id', 'year', 'branch_code', 'segment', 'number'];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    public $timestamps = false;

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
