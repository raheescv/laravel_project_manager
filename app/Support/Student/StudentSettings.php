<?php

namespace App\Support\Student;

use App\Models\Configuration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The school's student card rules (Settings → Student Settings).
 *
 * Read straight from `configurations` on each use: it is one indexed query, and
 * the overdraft limit guards money, so a stale cached copy is not worth saving it.
 */
final class StudentSettings
{
    public const KEY = 'student_settings';

    /** Sunday to Thursday, as ISO weekdays (1 = Monday … 7 = Sunday). */
    public const DEFAULT_SCHOOL_DAYS = [7, 1, 2, 3, 4];

    public const DEFAULT_PRE_ORDER_CUTOFF = '07:30';

    /** How far ahead a parent can pre-order a day. */
    public const PRE_ORDER_DAYS_AHEAD = 30;

    public function __construct(
        /** How far below zero a card may go on a purchase (school-wide). */
        public readonly float $overdraftLimit,
        public readonly float $topupMin,
        public readonly float $topupMax,
        /**
         * Where the school's parent_portal app is published. The API lives on the
         * school's host, the portal can live anywhere, so links in invite emails and
         * the way back from QPay need this address. Empty → PARENT_PORTAL_URL.
         */
        public readonly ?string $portalUrl = null,
        /** Parents may set up canteen pre-orders in the portal. */
        public readonly bool $preOrdersEnabled = false,
        /** Categories on the pre-order menu (a product's main or sub category). */
        public readonly array $preOrderCategoryIds = [],
        /** Orders for a day can be placed or changed until this time (HH:MM) that day. */
        public readonly string $preOrderCutoff = self::DEFAULT_PRE_ORDER_CUTOFF,
        /** ISO weekdays the canteen serves. */
        public readonly array $schoolDays = self::DEFAULT_SCHOOL_DAYS,
    ) {}

    public static function current(): self
    {
        $config = json_decode((string) Configuration::where('key', self::KEY)->value('value'), true) ?: [];

        return self::fromArray($config);
    }

    public static function fromArray(array $config): self
    {
        $portalUrl = trim((string) ($config['portal_url'] ?? ''));
        $cutoff = (string) ($config['pre_order_cutoff'] ?? '');
        $days = array_values(array_unique(array_filter(array_map('intval', (array) ($config['school_days'] ?? self::DEFAULT_SCHOOL_DAYS)), fn ($day) => $day >= 1 && $day <= 7)));
        sort($days);

        return new self(
            overdraftLimit: max(0, round((float) ($config['overdraft_limit'] ?? 0), 2)),
            topupMin: max(0, round((float) ($config['topup_min'] ?? 10), 2)),
            topupMax: max(0, round((float) ($config['topup_max'] ?? 1000), 2)),
            portalUrl: $portalUrl !== '' ? $portalUrl : null,
            preOrdersEnabled: (bool) ($config['pre_orders_enabled'] ?? false),
            preOrderCategoryIds: array_values(array_unique(array_filter(array_map('intval', (array) ($config['pre_order_category_ids'] ?? []))))),
            preOrderCutoff: preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $cutoff) ? $cutoff : self::DEFAULT_PRE_ORDER_CUTOFF,
            schoolDays: $days ?: self::DEFAULT_SCHOOL_DAYS,
        );
    }

    public function toArray(): array
    {
        return [
            'overdraft_limit' => $this->overdraftLimit,
            'topup_min' => $this->topupMin,
            'topup_max' => $this->topupMax,
            'portal_url' => $this->portalUrl,
            'pre_orders_enabled' => $this->preOrdersEnabled,
            'pre_order_category_ids' => $this->preOrderCategoryIds,
            'pre_order_cutoff' => $this->preOrderCutoff,
            'school_days' => $this->schoolDays,
        ];
    }

    /**
     * A page of the parent portal, e.g. portalLink('set-password/abc') →
     * https://parents.school.qa/#/set-password/abc. The portal routes on the #hash,
     * so it works from any folder with no server rewrites. Null when no address is set.
     */
    public function portalLink(string $path = ''): ?string
    {
        $base = $this->portalUrl ?: trim((string) config('services.parent_portal.url'));
        if ($base === '') {
            return null;
        }

        return rtrim(Str::before($base, '#'), '/').'/#/'.ltrim($path, '/');
    }

    /** Pre-orders are on and there is something on the menu. */
    public function preOrdersOpen(): bool
    {
        return $this->preOrdersEnabled && $this->preOrderCategoryIds !== [];
    }

    public function isSchoolDay(Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->schoolDays, true);
    }

    /** When orders for [$date] stop being accepted or changed. */
    public function preOrderDeadline(Carbon $date): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $this->preOrderCutoff));

        return $date->copy()->startOfDay()->setTime($hour, $minute);
    }

    /** Past days and today after the cut-off can no longer be changed. */
    public function preOrderLocked(Carbon $date): bool
    {
        return now()->greaterThanOrEqualTo($this->preOrderDeadline($date));
    }
}
