<?php

namespace App\Models;

use App\Models\Models\Views\Ledger;
use App\Services\TenantService;
use App\Support\TenantCache;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;
use RuntimeException;

/**
 * KYC columns are added in a loop by 2026_07_23_000001_add_kyc_fields_to_accounts_table, which Larastan cannot read.
 *
 * @property string|null $emergency_contact_no
 * @property string|null $po_box
 * @property string|null $passport_no
 * @property string|null $marital_status
 * @property string|null $occupation
 * @property string|null $job
 * @property string|null $sponsor_name
 * @property string|null $position_nature_of_business
 * @property string|null $residential_address
 * @property string|null $employer_address
 * @property string|null $contact_person
 * @property string|null $contact_person_mobile
 * @property string|null $cr_number
 * @property string|null $cp_number
 * @property string|null $eid_number
 * @property string|null $tax_card_no
 * @property string|null $monthly_income
 * @property string|null $id_expiry_date
 * @property string|null $cr_issue_date
 * @property string|null $cr_expiry_date
 * @property string|null $cp_issue_date
 * @property string|null $cp_expiry_date
 * @property string|null $eid_issue_date
 * @property string|null $eid_expiry_date
 * @property string|null $tax_card_issue_date
 * @property string|null $kyc_confirmed_at
 * @property int|null $kyc_confirmed_by
 */
class Account extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'account_type',
        'customer_type_id',
        'account_category_id',
        'name',
        'alias_name',
        'mobile',
        'whatsapp_mobile',
        'model',
        'email',
        'place',

        'dob',
        'id_no',
        'nationality',
        'company',
        'tax_no',
        'image',
        'credit_period_days',

        'emergency_contact_no',
        'po_box',
        'id_expiry_date',
        'passport_no',
        'marital_status',
        'occupation',
        'job',
        'sponsor_name',
        'position_nature_of_business',
        'monthly_income',
        'residential_address',
        'employer_address',
        'contact_person',
        'contact_person_mobile',
        'cr_number',
        'cr_issue_date',
        'cr_expiry_date',
        'cp_number',
        'cp_issue_date',
        'cp_expiry_date',
        'eid_number',
        'eid_issue_date',
        'eid_expiry_date',
        'tax_card_no',
        'tax_card_issue_date',
        'kyc_confirmed_at',
        'kyc_confirmed_by',

        'description',
        'opening_debit',
        'opening_credit',
        'second_reference_no',
        'is_cheque',
    ];

    protected $casts = [
        'is_cheque' => 'boolean',
    ];

    protected static function booted(): void
    {
        // The slug->id map and payment-method list are cached per tenant;
        // drop them whenever an account changes so postings never resolve
        // against a stale chart of accounts.
        $invalidate = function (): void {
            TenantCache::forget('accounts_slug_id_map');
        };
        static::saved($invalidate);
        static::deleted($invalidate);
        static::restored($invalidate);
    }

    /**
     * The current tenant's locked (system) accounts as slug => id.
     *
     * This is THE way to resolve a system account for journal postings —
     * never by display name, which is tenant-editable, and never via a raw
     * DB::table('accounts') query, which ignores TenantScope.
     *
     * Throws when no tenant is resolved: a missing map must surface as a loud
     * configuration error, not as journal entries posted to null account ids.
     */
    public static function slugIdMap(): array
    {
        if (! app(TenantService::class)->getCurrentTenantId()) {
            throw new RuntimeException('No tenant resolved; refusing to build the account slug map.');
        }

        return TenantCache::get('accounts_slug_id_map', []);
    }

    /**
     * The current tenant's system-account id for a slug, or a loud failure.
     *
     * A missing system account is a configuration error and must surface here,
     * not as a journal entry silently posted to a null account id.
     */
    public static function idBySlug(string $slug): int
    {
        return self::slugIdMap()[$slug]
            ?? throw new RuntimeException("The '{$slug}' system account is not configured for this tenant.");
    }

    public static function rules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'account_type' => ['required'],
            'name' => ['required', 'max:100'],
            'mobile' => ['max:15'],
            'model' => ['max:30'],
            'email' => ['max:50'],
            'unique_composite' => [
                Rule::unique(self::class)
                    ->where('tenant_id', $tenantId)
                    ->where(function ($query) {
                        return $query
                            ->where('account_type', request()->input('account_type'))
                            ->where('name', request()->input('name'))
                            ->where('mobile', request()->input('mobile'));
                    })
                    ->whereNull('deleted_at')
                    ->ignore($id),
            ],
        ], $merge);
    }

    public function getDropDownList($request)
    {
        $self = self::orderBy('name');
        $self = $self->when($request['query'] ?? '', function ($query, $value) {
            return $query->where(function ($q) use ($value): void {
                $value = trim($value);
                $q->where('accounts.name', 'like', "%{$value}%")
                    ->orWhere('accounts.mobile', 'like', "%{$value}%")
                    ->orWhere('accounts.email', 'like', "%{$value}%");
            });
        });
        $self = $self->when($request['account_type'] ?? '', function ($query, $value) {
            return $query->where('account_type', $value);
        });
        $self = $self->when($request['customer_type_id'] ?? '', function ($query, $value) {
            return $query->where('customer_type_id', $value);
        });
        $self = $self->when($request['account_category_id'] ?? '', function ($query, $value) {
            return $query->where('account_category_id', $value);
        });
        $self = $self->when($request['is_payment_method'] ?? '', function ($query, $value) {
            return $query->whereIn('id', tenant_cache('payment_methods', []));
        });
        $self = $self->when($request['model'] ?? '', function ($query, $value) {
            return $query->where('model', $value);
        });
        $serviceCategoryIds = ($request['rent_out_service'] ?? '') ? RentOut::serviceCategoryIds() : [];
        $self = $self->when($serviceCategoryIds, fn ($query, $ids) => $query->whereIn('id', $ids));
        $self = $self->limit($serviceCategoryIds ? max(count($serviceCategoryIds), 10) : 10);
        $self = $self->get(['name', 'mobile', 'email', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public function scopeVendor($query)
    {
        return $query->where('model', 'Vendor');
    }

    public function scopeCustomer($query)
    {
        return $query->where('model', 'Customer');
    }

    /**
     * Student accounts. A student's card balance is this account's own ledger
     * balance; the school-only fields live in student_details.
     */
    public function scopeStudent($query)
    {
        return $query->where('accounts.model', 'student');
    }

    public function isStudent(): bool
    {
        return strtolower((string) $this->model) === 'student';
    }

    /** @return HasOne<StudentDetail, $this> */
    public function studentDetail(): HasOne
    {
        return $this->hasOne(StudentDetail::class);
    }

    /**
     * Parent portal logins that may see this student.
     *
     * @return BelongsToMany<Guardian, $this>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student', 'account_id', 'guardian_id')
            ->withPivot(['relation', 'is_primary'])
            ->withTimestamps();
    }

    /** @return HasMany<AccountNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(AccountNote::class);
    }

    /** @return HasMany<Ledger, $this> */
    public function ledger(): HasMany
    {
        return $this->hasMany(Ledger::class, 'account_id');
    }

    /** @return HasMany<JournalEntry, $this> */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'account_id');
    }

    /** @return BelongsTo<CustomerType, $this> */
    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /** @return BelongsTo<AccountCategory, $this> */
    public function accountCategory(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class);
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<User, $this> */
    public function kycConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyc_confirmed_by');
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getNameAttribute($value)
    {
        return ucwords($value);
    }

    /**
     * Resolve a usable URL for the account's profile photo, falling back to the
     * default placeholder avatar when none has been uploaded.
     */
    public function getImageUrlAttribute(): string
    {
        $image = $this->attributes['image'] ?? null;
        if ($image && Storage::disk('public')->exists($image)) {
            return asset('storage/'.$image);
        }

        return secure_asset('assets/img/profile-photos/1.png');
    }
}
