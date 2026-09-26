<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

/**
 * A payment a tenant made to the installation owner. Platform-level like
 * Tenant itself, so it deliberately has no tenant scope.
 */
class TenantPayment extends Model
{
    /** @use HasFactory<\Database\Factories\TenantPaymentFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public const TYPES = [
        'amc' => 'AMC',
        'setup' => 'Setup',
        'other' => 'Other',
    ];

    /** @var list<string> */
    public const METHODS = ['Cash', 'Bank Transfer', 'Card', 'Cheque', 'Online'];

    protected $fillable = [
        'tenant_id',
        'paid_on',
        'type',
        'amount',
        'method',
        'reference',
        'note',
        'renewed_from',
        'renewed_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'decimal:2',
            'renewed_from' => 'date',
            'renewed_to' => 'date',
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'tenant_id' => ['required', 'exists:tenants,id'],
            'paid_on' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(self::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'method' => ['nullable', 'string', 'max:30'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withoutGlobalScopes();
    }
}
