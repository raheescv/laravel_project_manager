<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * A parent's login to the portal. Authenticates on the `parent` guard only, with
 * a Sanctum token the parent_portal app sends as a bearer token.
 *
 * Owns no ledger: everything a parent sees is read through the student accounts
 * linked in guardian_student, and every portal query must go through
 * students() so one family can never reach another family's child.
 */
class Guardian extends Authenticatable implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;
    use HasApiTokens;
    use SoftDeletes;

    public const RELATIONS = [
        'father' => 'Father',
        'mother' => 'Mother',
        'guardian' => 'Guardian',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'mobile',
        'email',
        'password',
        'status',
        'invite_token_hash',
        'invite_expires_at',
        'invited_at',
        'last_login_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invite_token_hash',
    ];

    protected $casts = [
        'password' => 'hashed',
        'invite_expires_at' => 'datetime',
        'invited_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected $auditExclude = ['password', 'remember_token', 'invite_token_hash', 'last_login_at'];

    public static function rules($id = 0, $merge = []): array
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'name' => ['required', 'max:100'],
            'mobile' => ['required', 'max:20', Rule::unique(self::class)->where('tenant_id', $tenantId)->whereNull('deleted_at')->ignore($id)],
            'email' => ['nullable', 'email', 'max:150'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ], $merge);
    }

    /** The student accounts this parent may see. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'guardian_student', 'guardian_id', 'account_id')
            ->withPivot(['relation', 'is_primary'])
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Invited but has not chosen a password yet. */
    public function isPendingInvite(): bool
    {
        return ! $this->password;
    }
}
