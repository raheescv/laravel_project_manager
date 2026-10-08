<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'endpoint',
        'method',
        'service_name',
        'app_version',
        'app_platform',
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

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'endpoint' => ['required', 'string'],
            'method' => ['required', 'string'],
            'status' => ['required', 'string'],
        ], $merge);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
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
