<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class ComboOffer extends Model
{
    protected $fillable = [
        'name',
        'count',
        'description',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'name' => ['required', Rule::unique(self::class)->ignore($id)],
            'count' => ['required'],
            'amount' => ['required'],
        ], $merge);
    }

    public function saleComboOffers()
    {
        return $this->hasMany(SaleComboOffer::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value) {
                $value = trim($value);

                return $q->where('name', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%")
                    ->orWhere('count', 'like', "%{$value}%")
                    ->orWhere('amount', 'like', "%{$value}%");
            });
        });
        $self = $self->active();
        $self = $self->limit(10);
        $self = $self->get(['name', 'description', 'count', 'amount', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
