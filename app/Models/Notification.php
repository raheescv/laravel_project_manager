<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'tenant_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
