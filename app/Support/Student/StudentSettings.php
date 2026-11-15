<?php

namespace App\Support\Student;

use App\Models\Branch;
use App\Models\Configuration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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

    public const DEFAULT_REGISTRATION_CLASSES = ['KG1', 'KG2', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

    public const DEFAULT_REGISTRATION_DIVISIONS = ['A', 'B', 'C', 'D', 'E', 'F'];

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
        /**
         * The public registration page takes forms. Its schools are the branches shown
         * on the showcase (Branches, "exclude from showcase" off): registrationBranches().
         */
        public readonly bool $registrationOpen = false,
        public readonly array $registrationClasses = self::DEFAULT_REGISTRATION_CLASSES,
        public readonly array $registrationDivisions = self::DEFAULT_REGISTRATION_DIVISIONS,
        /**
         * A form from a new parent is approved on the spot and the parent is emailed
         * their set-password link. A form naming an existing parent's mobile always
         * waits for the office.
         */
        public readonly bool $registrationAutoApprove = true,
        /** The cut-off falls on the day before the meal (18:00 the evening before), not on the day. */
        public readonly bool $preOrderCutoffDayBefore = false,
        /** Who gets the list of the next day's pre-orders once its cut-off passes. */
        public readonly array $preOrderSummaryEmails = [],
        /** The branch a paid pre-order's sale is booked at (the canteen's stock); empty = the first branch. */
        public readonly ?int $preOrderBranchId = null,
        /** The user recorded on pre-order sales; empty = the tenant's System user. */
        public readonly ?int $preOrderUserId = null,
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
            // Before the switch existed, listing schools in Settings was what opened the page.
            registrationOpen: (bool) ($config['registration_enabled'] ?? self::listFrom($config['registration_schools'] ?? []) !== []),
            registrationClasses: self::listFrom($config['registration_classes'] ?? []) ?: self::DEFAULT_REGISTRATION_CLASSES,
            registrationDivisions: self::listFrom($config['registration_divisions'] ?? []) ?: self::DEFAULT_REGISTRATION_DIVISIONS,
            registrationAutoApprove: (bool) ($config['registration_auto_approve'] ?? true),
            preOrderCutoffDayBefore: (bool) ($config['pre_order_cutoff_day_before'] ?? false),
            preOrderSummaryEmails: self::emailsFrom($config['pre_order_summary_emails'] ?? []),
            preOrderBranchId: ((int) ($config['pre_order_branch_id'] ?? 0)) ?: null,
            preOrderUserId: ((int) ($config['pre_order_user_id'] ?? 0)) ?: null,
        );
    }

    /**
     * A saved list, or the one-per-line text from the settings box.
     *
     * @param  array<int, string>|string  $value
     * @return array<int, string>
     */
    private static function listFrom(array|string $value): array
    {
        $items = is_array($value) ? $value : preg_split('/\R/', $value);

        return array_values(array_unique(array_filter(array_map(fn ($item) => trim((string) $item), $items), fn (string $item) => $item !== '')));
    }

    /**
     * Addresses from a saved list or the settings box (one per line, or commas).
     *
     * @param  array<int, string>|string  $value
     * @return array<int, string>
     */
    private static function emailsFrom(array|string $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[\s,;]+/', $value);

        return array_values(array_unique(array_filter(
            array_map(fn ($item) => Str::lower(trim((string) $item)), $items),
            fn (string $item) => filter_var($item, FILTER_VALIDATE_EMAIL) !== false,
        )));
    }

    public function toArray(): array
    {
        return [
            'registration_enabled' => $this->registrationOpen,
            'registration_classes' => $this->registrationClasses,
            'registration_divisions' => $this->registrationDivisions,
            'registration_auto_approve' => $this->registrationAutoApprove,
            'overdraft_limit' => $this->overdraftLimit,
            'topup_min' => $this->topupMin,
            'topup_max' => $this->topupMax,
            'portal_url' => $this->portalUrl,
            'pre_orders_enabled' => $this->preOrdersEnabled,
            'pre_order_category_ids' => $this->preOrderCategoryIds,
            'pre_order_cutoff' => $this->preOrderCutoff,
            'pre_order_cutoff_day_before' => $this->preOrderCutoffDayBefore,
            'pre_order_summary_emails' => $this->preOrderSummaryEmails,
            'pre_order_branch_id' => $this->preOrderBranchId,
            'pre_order_user_id' => $this->preOrderUserId,
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

    /**
     * The schools a parent picks from on the registration page: the branches shown
     * on the showcase, by name. A registered child is attached to the one picked.
     *
     * @return Collection<int, Branch>
     */
    public static function registrationBranches(): Collection
    {
        return Branch::query()->where('exclude_from_showcase', false)->orderBy('name')->get(['id', 'name', 'location']);
    }

    /** The registration page is switched on and has at least one school to offer. */
    public function registrationAvailable(): bool
    {
        return $this->registrationOpen && self::registrationBranches()->isNotEmpty();
    }

    /** Pre-orders are on and there is something on the menu. */
    public function preOrdersOpen(): bool
    {
        return $this->preOrdersEnabled && $this->preOrderCategoryIds !== [];
    }

    /**
     * Where pre-order sales are booked: the branch chosen in Settings, else the first
     * branch, so a school that never picked one keeps taking orders.
     */
    public function preOrderBranch(): ?int
    {
        return $this->preOrderBranchId ?? Branch::query()->orderBy('id')->value('id');
    }

    public function isSchoolDay(Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->schoolDays, true);
    }

    /** When orders for [$date] stop being accepted or changed: the cut-off time on that day, or on the day before. */
    public function preOrderDeadline(Carbon $date): Carbon
    {
        [$hour, $minute] = array_map('intval', explode(':', $this->preOrderCutoff));
        $day = $date->copy()->startOfDay();

        return ($this->preOrderCutoffDayBefore ? $day->subDay() : $day)->setTime($hour, $minute);
    }

    /** "07:30" or "18:00 the day before", for the screens that show the rule. */
    public function preOrderCutoffLabel(): string
    {
        $time = Carbon::createFromFormat('H:i', $this->preOrderCutoff)->format('g:i A');

        return $this->preOrderCutoffDayBefore ? $time.' the day before' : $time;
    }

    /**
     * The day the kitchen prepares next, which the pre-order list prints by default:
     * tomorrow once today's cut-off has passed with a day-before cut-off, else today.
     */
    public function preOrderPrintDay(): Carbon
    {
        $closedForTomorrow = $this->preOrderCutoffDayBefore && now()->gte(today()->setTimeFromTimeString($this->preOrderCutoff));

        return $closedForTomorrow ? today()->addDay() : today();
    }

    /** Past days and days whose cut-off has passed can no longer be changed. */
    public function preOrderLocked(Carbon $date): bool
    {
        return now()->greaterThanOrEqualTo($this->preOrderDeadline($date));
    }
}
