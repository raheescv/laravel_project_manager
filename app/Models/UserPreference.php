<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user UI preferences (table column visibility, saved layout choices, ...).
 *
 * Keyed by a dotted string so any screen can claim its own namespace, e.g.
 * `property.table.columns`. Values are stored as JSON, so scalars, lists and
 * maps are all fine.
 */
class UserPreference extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getValue(string $key, $default = null, ?int $userId = null)
    {
        $userId ??= auth()->id();
        if (! $userId) {
            return $default;
        }

        $preference = self::where('user_id', $userId)->where('key', $key)->first();

        return $preference ? ($preference->value ?? $default) : $default;
    }

    public static function setValue(string $key, $value, ?int $userId = null): void
    {
        $userId ??= auth()->id();
        if (! $userId) {
            return;
        }

        self::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    public static function forgetValue(string $key, ?int $userId = null): void
    {
        $userId ??= auth()->id();
        if (! $userId) {
            return;
        }

        self::where('user_id', $userId)->where('key', $key)->delete();
    }
}
