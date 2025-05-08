<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePackage extends Model
{
    protected $fillable = [
        'name',
        'description',
        'service_count',
        'amount',
        'is_active',
    ];

    public function salePackages()
    {
        return $this->hasMany(SalePackage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value) {
                $value = trim($value);

                return $q->where('name', 'like', "%{$value}%")
                    ->orWhere('description', 'like', "%{$value}%")
                    ->orWhere('service_count', 'like', "%{$value}%")
                    ->orWhere('color', 'like', "%{$value}%")
                    ->orWhere('amount', 'like', "%{$value}%");
            });
        });
        $self = $self->active();
        $self = $self->limit(10);
        $self = $self->get(['name', 'description', 'service_count', 'amount', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }
}
