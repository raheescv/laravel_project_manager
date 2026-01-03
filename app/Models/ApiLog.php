<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'endpoint',
        'method',
        'request',
        'response',
        'status',
        'description',
        'username',
        'password',
        'token',
        'user_id',
        'user_name',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'endpoint' => ['required', 'string'],
            'method' => ['required', 'string'],
            'status' => ['required', 'string'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
