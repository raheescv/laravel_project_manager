<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

class Issue extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'account_id',
        'remarks',
        'no_of_items_out',
        'no_of_items_in',
    ];

    protected $casts = [
        'no_of_items_out' => 'decimal:2',
        'no_of_items_in' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public static function rules(int $id = 0, array $merge = []): array
    {
        return array_merge([
            'account_id' => ['required', 'exists:accounts,id'],
            'remarks' => ['nullable', 'string'],
        ], $merge);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IssueItem::class);
    }
}
