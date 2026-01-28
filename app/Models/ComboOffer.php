<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class ComboOffer extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
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
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
            'count' => ['required'],
            'amount' => ['required'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
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
