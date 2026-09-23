<?php

namespace App\Models;

use App\Events\BranchUpdated;
use App\Traits\BelongsToTenant;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements AuditableContracts
{
    use Auditable, HasFactory, HasRoles, Notifiable;
    use BelongsToTenant;
    use HasApiTokens;

    protected $fillable = [
        'tenant_id',
        'type',
        'name',
        'code',
        'email',
        'mobile',
        'image',
        'is_admin',
        'is_super_admin',
        'default_branch_id',
        'email_verified_at',
        'password',
        'pin',
        'dob',
        'doj',
        'place',
        'nationality',
        'allowance',
        'salary',
        'hra',
        'max_discount_per_sale',
        'designation_id',
        'order_no',
        'is_locked',
        'is_active',
        'is_whatsapp_enabled',
        'second_reference_no',
        'telegram_chat_id',
        'is_telegram_enabled',
        'is_browser_notification_enabled',
    ];

    protected $hidden = [
        'password',
        'pin',
        'pin_lookup',
        'remember_token',
    ];

    protected $auditExclude = [
        'remember_token',
    ];

    public static function createRules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->where('tenant_id', $tenantId)->ignore($id)],
            'password' => ['required'],
        ], $merge);
    }

    public static function updateRules($id = 0, $merge = [])
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required'],
            'email' => ['required', Rule::unique(self::class, 'email')->where('tenant_id', $tenantId)->ignore($id)],
        ], $merge);
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($user): void {
            if ($user->isDirty('default_branch_id')) {
                // event(new BranchUpdated($user, $user->default_branch_id));
            }
        });
    }

    public function branches()
    {
        return $this->hasMany(UserHasBranch::class, 'user_id');
    }

    /**
     * Stock handed over to this employee and not yet returned or sold.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'employee_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'default_branch_id');
    }

    /**
     * The branches this user may work as — their assigned branches, the same
     * list the web branch popup offers. An account with none assigned keeps its
     * default branch, which is where it has always posted.
     *
     * @return array<int, int>
     */
    public function operableBranchIds(): array
    {
        $assigned = $this->branches()->pluck('branch_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        if ($assigned !== []) {
            return $assigned;
        }

        return $this->default_branch_id ? [(int) $this->default_branch_id] : [];
    }

    /**
     * The branch a mobile request acts on: the one it asks for when this user may
     * work there, otherwise their default branch. A branch the user has no
     * assignment to is never honoured, so a request cannot post into it.
     */
    public function operatingBranchId(mixed $requested): ?int
    {
        if (is_numeric($requested) && in_array((int) $requested, $this->operableBranchIds(), true)) {
            return (int) $requested;
        }

        return $this->default_branch_id ? (int) $this->default_branch_id : null;
    }

    public function attendances()
    {
        return $this->hasMany(UserAttendance::class, 'employee_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    /**
     * Resolved avatar URL for web blades — the uploaded photo when set, else the
     * default placeholder. (The mobile API deliberately returns a root-relative
     * path instead; see AuthUserResource.)
     */
    public function getPhotoUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : secure_asset('assets/img/profile-photos/1.png');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Replaces the `hashed` cast on `pin` so the write also derives the indexed
     * lookup digest login needs — the two must never drift apart, so they are set
     * together or not at all.
     *
     * Already-hashed input is passed through untouched, matching what the `hashed`
     * cast did (the single-use data-migration commands copy stored hashes across).
     * The plaintext isn't available in that case, so the digest is left NULL and
     * that user takes the fallback path until their next successful sign-in.
     */
    protected function pin(): Attribute
    {
        return Attribute::set(function ($value) {
            if ($value === null || $value === '') {
                return ['pin' => null, 'pin_lookup' => null];
            }

            $value = (string) $value;

            if (filled(password_get_info($value)['algo'])) {
                return ['pin' => $value, 'pin_lookup' => null];
            }

            return [
                'pin' => Hash::make($value),
                'pin_lookup' => static::pinLookup($value),
            ];
        });
    }

    /**
     * Deterministic keyed digest of a plaintext PIN, used only to locate the
     * candidate row before the real bcrypt verification. Keyed with the app key so
     * a leaked database on its own doesn't reduce a 4-digit PIN to a lookup table.
     *
     * Rotating APP_KEY invalidates every stored digest. Recover the same way you
     * would for any keyed blind index — clear them and let sign-ins repopulate:
     * `UPDATE users SET pin_lookup = NULL;`
     */
    public static function pinLookup(string $pin): string
    {
        return hash_hmac('sha256', $pin, (string) config('app.key'));
    }

    /**
     * Whether this account may only ever see its own work in the mobile app.
     *
     * True for a rank-and-file employee: their Sales list, Sales Returns,
     * Dashboard and Reports are all hard-scoped to what they created / are
     * attributed with, whatever filters the client sends. Admins — and
     * back-office 'user'-type accounts — get the full branch-scoped view.
     *
     * Deliberately not permission-driven: a cashier is granted
     * `report.sales overview` so the dashboard loads at all; what that
     * permission opens is *their* overview, not the shop's.
     */
    public function seesOnlyOwnRecords(): bool
    {
        return $this->type === 'employee' && ! $this->is_admin;
    }

    /**
     * The tenant's "System" account (seeded as the first user) that unattended
     * work - the scheduled day-session open/close - is stamped with. Null when a
     * tenant never got one; callers store null then, which still reads "System".
     */
    public static function systemUserId(int $tenantId): ?int
    {
        return static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('name', 'System')
            ->orderBy('id')
            ->value('id');
    }

    public function scopeEmployee($query)
    {
        return $query->where('type', 'employee');
    }

    public function scopeUser($query)
    {
        return $query->where('type', 'user');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Shared filter builder for both roster screens.
     *
     * The account type is driven by the 'type' filter — 'employee' when it is
     * omitted, so existing employee callers keep their behaviour — which lets
     * the Users list (type 'user') reuse the same role / designation / branch
     * filtering instead of duplicating it.
     */
    public static function getFilteredQuery(array $filters = [])
    {
        return static::query()
            ->where('users.type', $filters['type'] ?? 'employee')
            ->when($filters['search'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value) {
                    $value = trim($value);

                    return $q->where('users.name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('mobile', 'like', "%{$value}%")
                        ->orWhere('place', 'like', "%{$value}%")
                        ->orWhere('nationality', 'like', "%{$value}%");
                });
            })
            ->when($filters['role_id'] ?? null, function ($query) use ($filters): void {
                $query->whereHas('roles', function ($q) use ($filters): void {
                    $q->where('id', $filters['role_id']);
                });
            })
            // The condition passed to when() is a boolean, so the closure's second
            // argument would be `true` rather than the requested flag — read the
            // value off $filters instead, or "Inactive" silently lists active users.
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters): void {
                $query->where('users.is_active', (int) $filters['is_active']);
            })
            ->when($filters['designation_id'] ?? null, function ($query, $value): void {
                $query->where('designation_id', $value);
            })
            ->when($filters['branch_id'] ?? null, function ($query) use ($filters): void {
                $query->whereHas('branches', function ($q) use ($filters): void {
                    $q->where('user_has_branches.branch_id', $filters['branch_id']);
                });
            });
    }

    public function getDropDownList(array $request)
    {
        $self = self::orderBy('name', 'ASC')
            ->when($request['query'] ?? '', function ($query, $value) {
                return $query->where(function ($q) use ($value): void {
                    $value = trim($value);
                    $q->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere('mobile', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                });
            })
            ->whereHas('branches', function ($query) {
                return $query->where('user_has_branches.branch_id', session('branch_id'));
            })
            ->when($request['type'] ?? '', function ($query, $value) {
                return $query->where('type', $value);
            })
            ->active()
            // ->limit(10)
            ->get(['name', 'email', 'mobile', 'id'])->toArray();
        $return['items'] = $self;

        return $return;
    }

    public static function validateMaxDiscount(float $max_discount_per_sale, float $grossAmount, float $totalDiscount)
    {
        if (! $max_discount_per_sale) {
            return; // No limit set, allow any discount
        }

        if ($grossAmount <= 0) {
            return; // No gross amount, nothing to validate
        }

        $discountPercentage = round(($totalDiscount / $grossAmount) * 100, 2);
        if ($max_discount_per_sale == 0) {
            throw new Exception("You don't have the permission to give discount");
        }
        if ($discountPercentage > $max_discount_per_sale) {
            throw new Exception("Total discount percentage ({$discountPercentage}%) exceeds your maximum allowed discount per sale ({$max_discount_per_sale}%).");
        }
    }
}
