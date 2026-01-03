<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class Branch extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'location',
        'mobile',
        'moq_sync',
    ];

    protected $casts = [
        'moq_sync' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'tenant_id' => ['required'],
            'name' => ['required', Rule::unique(self::class, 'name')->ignore($id)],
            'code' => ['required', Rule::unique(self::class, 'code')->ignore($id)],
        ], $merge);
    }

    /**
     * Get the tenant that owns this branch
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = trim($value);
    }

    public function getAssignedBranchDropDownList($request)
    {
        $userId = $request['user_id'] ?? null;
        if (! $userId) {
            $userId = Auth::id();
        }
        $user = User::find($userId);
        $self = self::orderBy('name');
        $assigned_ids = [];
        if ($user) {
            $assigned_ids = $user->branches->pluck('branch_id', 'branch_id')->toArray();
        }
        $self = $self->whereIn('id', $assigned_ids);
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    /**
     * Get all users assigned to this branch
     */
    public function assignedUsers(): HasMany
    {
        return $this->hasMany(UserHasBranch::class, 'branch_id');
    }
}
