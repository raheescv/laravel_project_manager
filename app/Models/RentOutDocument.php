<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class RentOutDocument extends Model implements AuditableContracts
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'rent_out_id',
        'document_type_id',
        'name',
        'path',
        'remarks',
        'created_by',
    ];

    public static function rules($id = 0): array
    {
        return [
            'rent_out_id' => 'required|exists:rent_outs,id',
            'document_type_id' => 'required|exists:document_types,id',
            'name' => 'required|string|max:255',
            'path' => 'required|string',
        ];
    }

    public function rentOut(): BelongsTo
    {
        return $this->belongsTo(RentOut::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
