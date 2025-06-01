<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'location',
        'mobile',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class, 'name')->ignore($id)],
            'code' => ['required', Rule::unique(self::class, 'code')->ignore($id)],
        ], $merge);
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
}
