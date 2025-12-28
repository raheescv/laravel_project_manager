<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PackageCategory extends Model
{
    protected $fillable = [
        'name',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
            'price' => ['required', 'numeric', 'min:0'],
        ], $merge);
    }

    public function scopeActive($query)
    {
        return $query;
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value) {
                $value = trim($value);

                return $q->where('name', 'like', "%{$value}%")
                    ->orWhere('price', 'like', "%{$value}%");
            });
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'price', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
