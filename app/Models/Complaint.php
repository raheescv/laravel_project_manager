<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Complaint extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'complaint_category_id',
        'name',
        'arabic_name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function rules($id = 0): array
    {
        return [
            'complaint_category_id' => 'required|exists:complaint_categories,id',
            'name' => 'required|string|max:255',
        ];
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('name', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['complaint_category_id'] ?? '', function ($query, $value) {
            return $query->where('complaint_category_id', $value);
        });
        $self = $self->where('is_active', true);
        $self = $self->limit(10);
        $self = $self->get(['name', 'id', 'complaint_category_id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ComplaintCategory::class, 'complaint_category_id');
    }
}
