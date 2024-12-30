<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', 'max:10', Rule::unique(self::class, 'name')->ignore($id)],
            'code' => ['required', 'max:10', Rule::unique(self::class, 'code')->ignore($id)],
        ], $merge);
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            $query->where('name', 'like', "%{$value}%")
                ->orWhere('code', 'like', "%{$value}%");
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'code', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
