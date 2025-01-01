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
        'name',
        'email',
        'mobile',
        'password',
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
            'mobile' => ['required'],
            'password' => ['required'],
        ], $merge);
    }

    public static function updateRules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->ignore($id)],
            'mobile' => ['required'],
        ], $merge);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
