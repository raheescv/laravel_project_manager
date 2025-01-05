<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Configuration extends Model implements AuditableContracts
{
    use Auditable;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'key' => ['required', Rule::unique(self::class)->ignore($id)],
            'value' => ['required'],
        ], $merge);
    }
}
