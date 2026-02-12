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
        'type',
        'account_id',
        'date',
        'remarks',
        'signature',
        'no_of_items_out',
        'no_of_items_in',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'no_of_items_out' => 'decimal:2',
        'no_of_items_in' => 'decimal:2',
        'balance' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public static function rules(int $id = 0, array $merge = []): array
    {
        return array_merge([
            'type' => ['required', 'in:issue,return'],
            'account_id' => ['required', 'exists:accounts,id'],
            'date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'signature' => ['nullable', 'string'],
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(IssueItem::class);
    }
}
