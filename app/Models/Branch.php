<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'code',
        'location',
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
}
