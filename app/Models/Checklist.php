<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Checklist extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'category',
        'name',
        'property_type_id',
        'image_path',
        'sort_order',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Web-facing URL for the master image, or null when none is set. */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    public static function rules($id = 0): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
        ];
    }

    public function getDropDownList($request)
    {
        $self = self::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%")
                    ->orWhere('category', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['category'] ?? '', function ($query, $value) {
            return $query->where('category', $value);
        });
        $self = $self->limit(25);
        $self = $self->get(['id', 'name', 'category', 'image_path'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function lines(): HasMany
    {
        return $this->hasMany(RentOutChecklistLine::class, 'checklist_id');
    }

    /**
     * Property type this item applies to. A null property_type_id means the item
     * is universal (shown for every property type).
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }
}
