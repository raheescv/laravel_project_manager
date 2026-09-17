<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What a meal product serves on each weekday — the same every week (Students →
 * Canteen Menu). Courses run down, weekdays across, like a caterer's menu sheet.
 *
 * `courses`: [{name, note, dishes: {"<iso weekday>": {name, description}}}]
 */
class CanteenMenu extends Model
{
    use BelongsToTenant;

    public const MAX_COURSES = 8;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'courses',
        'updated_by',
    ];

    protected $casts = [
        'courses' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Weekdays with at least one dish — the days the meal is served. Null when no
     * dish is entered at all: a meal without a menu is served every school day.
     *
     * @return array<int, int>|null
     */
    public function servedWeekdays(): ?array
    {
        $days = [];
        foreach ((array) $this->courses as $course) {
            foreach ((array) ($course['dishes'] ?? []) as $weekday => $dish) {
                if (filled($dish['name'] ?? null)) {
                    $days[(int) $weekday] = true;
                }
            }
        }
        $days = array_keys($days);
        sort($days);

        return $days ?: null;
    }

    /** @return array<int, array{course: string, note: ?string, name: string, description: ?string}> */
    public function dishesFor(int $weekday): array
    {
        $dishes = [];
        foreach ((array) $this->courses as $course) {
            $dish = $course['dishes'][$weekday] ?? $course['dishes'][(string) $weekday] ?? null;
            if (filled($dish['name'] ?? null)) {
                $dishes[] = [
                    'course' => (string) ($course['name'] ?? ''),
                    'note' => filled($course['note'] ?? null) ? (string) $course['note'] : null,
                    'name' => (string) $dish['name'],
                    'description' => filled($dish['description'] ?? null) ? (string) $dish['description'] : null,
                ];
            }
        }

        return $dishes;
    }
}
