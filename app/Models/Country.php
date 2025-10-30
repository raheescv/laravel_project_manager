<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    Const INDIA = 105;
    Const QATAR = 187;

    protected $fillable = [
        'name',
        'code',
        'phone_code',
        'status',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
            'code' => ['required', Rule::unique(self::class)->ignore($id)],
        ], $merge);
    }

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where('name', 'like', "%{$value}%")->orWhere('code', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'code', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
