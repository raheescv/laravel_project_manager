<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class PackageCategory extends Model
{
    protected $fillable = [
        'name',
        'price',
        'frequency',
        'no_of_visits',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'no_of_visits' => 'integer',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
            'price' => ['required', 'numeric', 'min:0'],
            'frequency' => ['nullable', 'string', 'in:'.implode(',', array_keys(packageFrequency()))],
            'no_of_visits' => ['nullable', 'integer', 'min:0'],
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
                    ->orWhere('price', 'like', "%{$value}%")
                    ->orWhere('frequency', 'like', "%{$value}%")
                    ->orWhere('no_of_visits', 'like', "%{$value}%");
            });
        });
        $self = $self->limit(10);
        $self = $self->get(['name', 'price', 'frequency', 'no_of_visits', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
