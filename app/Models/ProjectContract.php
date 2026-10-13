<?php

namespace App\Models;

use App\Enums\ProjectContract\ContractStatus;
use App\Enums\ProjectContract\PaymentFrequency;
use App\Models\Scopes\AssignedBranchScope;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A contract with a vendor (an `accounts` row where model = Vendor) for a
 * project. Unlike a rent-out, its payment terms are not generated: the user
 * adds one term at a time as the project plan needs it, and `no_of_terms` is
 * only the planned count.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $branch_id
 * @property string $reference_no
 * @property string $project_name
 * @property int $account_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $no_of_terms
 * @property PaymentFrequency $payment_frequency
 * @property string $contract_amount
 * @property ContractStatus $status
 * @property string|null $remark
 */
class ProjectContract extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;

    /** @use HasFactory<\Database\Factories\ProjectContractFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'reference_no',
        'project_name',
        'account_id',
        'start_date',
        'end_date',
        'no_of_terms',
        'payment_frequency',
        'contract_amount',
        'status',
        'remark',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'no_of_terms' => 'integer',
            'contract_amount' => 'decimal:2',
            'status' => ContractStatus::class,
            'payment_frequency' => PaymentFrequency::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new AssignedBranchScope());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'project_name' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'no_of_terms' => ['required', 'integer', 'min:1'],
            'payment_frequency' => ['required'],
            'contract_amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /** The next PC-0001 style reference for the current tenant, counting deleted contracts so a number is never reused. */
    public static function nextReferenceNo(): string
    {
        $last = static::withoutGlobalScope(AssignedBranchScope::class)->withTrashed()->max('id') ?? 0;
        $next = (int) static::withoutGlobalScope(AssignedBranchScope::class)->withTrashed()->count() + 1;
        $number = max($next, (int) $last + 1);

        do {
            $reference = 'PC-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (static::withoutGlobalScope(AssignedBranchScope::class)->withTrashed()->where('reference_no', $reference)->exists());

        return $reference;
    }

    public function scopeFilter(Builder $query, array $filter): Builder
    {
        return $query
            ->when($filter['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('project_name', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('vendor', fn (Builder $vendor) => $vendor->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filter['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filter['account_id'] ?? null, fn (Builder $query, $vendorId) => $query->where('account_id', $vendorId))
            ->when($filter['branch_id'] ?? null, fn (Builder $query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filter['from_date'] ?? null, fn (Builder $query, string $date) => $query->where('end_date', '>=', $date))
            ->when($filter['to_date'] ?? null, fn (Builder $query, string $date) => $query->where('start_date', '<=', $date));
    }

    /** @return BelongsTo<Account, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ProjectContractPaymentTerm, $this> */
    public function terms(): HasMany
    {
        return $this->hasMany(ProjectContractPaymentTerm::class)->orderBy('due_date')->orderBy('id');
    }

    /** @return HasMany<ProjectContractPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(ProjectContractPayment::class);
    }

    /** @return HasMany<ProjectContractExtension, $this> */
    public function extensions(): HasMany
    {
        return $this->hasMany(ProjectContractExtension::class);
    }

    /** @return HasMany<ProjectContractSecurity, $this> */
    public function securities(): HasMany
    {
        return $this->hasMany(ProjectContractSecurity::class);
    }

    /** @return HasMany<ProjectContractDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ProjectContractDocument::class);
    }

    /** @return HasMany<ProjectContractNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectContractNote::class);
    }

    /** @return HasMany<ProjectContractApproval, $this> */
    public function approvals(): HasMany
    {
        return $this->hasMany(ProjectContractApproval::class);
    }

    /** Total of posted (not reversed) payments. */
    public function paidTotal(): float
    {
        return (float) $this->payments()->where('status', ProjectContractPayment::STATUS_POSTED)->sum('amount');
    }

    /** Total amount already scheduled into payment terms. */
    public function scheduledTotal(): float
    {
        return (float) $this->terms()->sum('amount');
    }

    public function isActive(): bool
    {
        return $this->status === ContractStatus::Active;
    }
}
