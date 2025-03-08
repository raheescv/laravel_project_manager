<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\Rule;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'type',
        'name',
        'code',
        'email',
        'mobile',
        'is_admin',
        'default_branch_id',
        'email_verified_at',
        'password',
        'pin',
        'dob',
        'doj',
        'place',
        'nationality',
        'allowance',
        'salary',
        'hra',
        'is_locked',
        'is_active',
        'is_whatsapp_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static function createRules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->ignore($id)],
            'password' => ['required'],
        ], $merge);
    }

    public static function updateRules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->ignore($id)],
        ], $merge);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function scopeEmployee($query)
    {
        return $query->where('type', 'employee');
    }

    public function scopeUser($query)
    {
        return $query->where('type', 'user');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('mobile', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['type'] ?? '', function ($query, $value) {
            return $query->where('type', $value);
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'email', 'mobile', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
