<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
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

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'endpoint' => ['required', 'string'],
            'method' => ['required', 'string'],
            'status' => ['required', 'string'],
        ], $merge);
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
