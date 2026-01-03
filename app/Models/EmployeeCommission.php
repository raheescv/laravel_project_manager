<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCommission extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'employee_id',
        'commission_percentage',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $casts = [
        'commission_percentage' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
