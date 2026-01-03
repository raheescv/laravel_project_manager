<?php

namespace App\Models;

use App\Models\Scopes\AssignedBranchScope;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Journal extends Model implements AuditableContracts
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'date',
        'description',
        'remarks',
        'reference_number',
        'person_name',
        'source',
        'model',
        'model_id',
        'created_by',
        'deleted_by',
    ];

    public static function rules($id = 0, $merge = [])
    {
        return array_merge([
            'branch_id' => ['required'],
            'date' => ['required'],
            'description' => ['required'],
            'created_by' => ['required'],
        ], $merge);
    }

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
        static::addGlobalScope(new AssignedBranchScope());
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'model_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'model_id');
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class, 'model_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
