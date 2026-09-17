<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContracts;

/**
 * The school-only facts about a student account (see the student_details migration).
 *
 * Audited, so every card assignment, block and unblock is on record without a
 * separate card history table.
 */
class StudentDetail extends Model implements AuditableContracts
{
    use Auditable;
    use BelongsToTenant;

    public const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'graduated' => 'Graduated',
    ];

    public const GENDERS = [
        'male' => 'Male',
        'female' => 'Female',
    ];

    public const CARD_ACTIVE = 'active';

    public const CARD_BLOCKED = 'blocked';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'admission_no',
        'gender',
        'grade',
        'section',
        'status',
        'card_uid',
        'card_status',
        'card_blocked_at',
        'card_blocked_by_type',
        'card_blocked_by_id',
        'card_block_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'card_blocked_at' => 'datetime',
    ];

    public static function rules($id = 0, $merge = []): array
    {
        $tenantId = self::getCurrentTenantId();

        return array_merge([
            'account_id' => ['required'],
            'admission_no' => ['required', 'max:30', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
            'gender' => ['nullable', Rule::in(array_keys(self::GENDERS))],
            'grade' => ['nullable', 'max:30'],
            'section' => ['nullable', 'max:30'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'card_uid' => ['nullable', 'max:40', Rule::unique(self::class)->where('tenant_id', $tenantId)->ignore($id)],
        ], $merge);
    }

    /**
     * The one spelling a card UID is stored and matched in.
     *
     * Readers disagree on presentation — "04:A2:1B:9C", "04 a2 1b 9c", "04a21b9c" —
     * so separators are dropped and hex is upper-cased. A reader that emits the
     * UID as a decimal number yields a different value and cannot be matched;
     * cards should be enrolled with the same device family the POS uses.
     */
    public static function normalizeCardUid(?string $uid): ?string
    {
        $uid = strtoupper(preg_replace('/[^0-9a-fA-F]/', '', (string) $uid));

        return $uid === '' ? null : $uid;
    }

    public function setCardUidAttribute($value): void
    {
        $this->attributes['card_uid'] = self::normalizeCardUid($value);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isCardBlocked(): bool
    {
        return $this->card_status === self::CARD_BLOCKED;
    }

    public function classLabel(): string
    {
        return trim(implode(' - ', array_filter([$this->grade, $this->section])));
    }
}
