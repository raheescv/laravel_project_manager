<?php

namespace App\Support;

use App\Models\Configuration;
use App\Models\User;

/**
 * The editable lead dropdowns: sources and statuses, each with sub options
 * keyed by their parent, and an order number per status. Managed from
 * Settings → Lead Settings, stored in configurations the same shape accounts
 * used, and read once per request (the board asks for statuses per card).
 *
 * Until a list is saved the built-in defaults apply, so a tenant that never
 * opens the screen keeps the lists it always had.
 */
class LeadOptions
{
    public const SOURCES = 'lead_sources';

    public const STATUSES = 'lead_statuses';

    public const STATUS_ORDER = 'lead_statuses_order';

    public const SUB_SOURCES = 'lead_sub_sources';

    public const SUB_STATUSES = 'lead_sub_statuses';

    public const ASSIGNEE_DESIGNATIONS = 'lead_assignee_designations';

    private const MEMO = 'lead-options.memo';

    public const DEFAULT_SOURCES = [
        'Social Media', 'Facebook', 'Instagram', 'Snapchat', 'YouTube', 'SMS', 'E-mail Campaign',
        'Outdoor Marketing', 'Personal', 'Walk-In', 'Local Broker', 'International Broker',
        'Open House', 'Exhibition', 'Call', 'WhatsApp Msg', 'Other',
    ];

    public const DEFAULT_STATUSES = [
        'New Lead', 'Follow Up', 'Interested', 'Not Interested', 'Low Budget', 'Visit Scheduled',
        'Closed Deal', 'Shopping For Info', 'Call Back', 'Same Day Call Back', 'No Answer',
        'Whatsapp Only', 'Dead Lead', 'Rejected', 'Drop', 'Follow Up For Visit',
    ];

    /** @return array<string, string> */
    public static function sources(): array
    {
        return self::pairs(self::stored()[self::SOURCES] ?? self::DEFAULT_SOURCES);
    }

    /** @return array<string, string> statuses by order no, unordered ones last and alphabetical */
    public static function statuses(): array
    {
        $stored = self::stored();
        if (! isset($stored[self::STATUSES])) {
            return self::pairs(self::DEFAULT_STATUSES);
        }

        $orders = self::statusOrders();
        $statuses = self::pairs($stored[self::STATUSES]);
        uksort($statuses, function (string $a, string $b) use ($orders): int {
            $orderA = $orders[$a] ?? PHP_INT_MAX;
            $orderB = $orders[$b] ?? PHP_INT_MAX;

            return $orderA === $orderB ? strcasecmp($a, $b) : $orderA <=> $orderB;
        });

        return $statuses;
    }

    /** @return array<string, int> */
    public static function statusOrders(): array
    {
        return array_map('intval', array_filter((array) (self::stored()[self::STATUS_ORDER] ?? []), 'is_numeric'));
    }

    /** @return array<string, list<string>> sub options keyed by parent */
    public static function subOptions(string $key): array
    {
        return array_map(
            fn ($subs) => array_values(array_filter(array_map('trim', (array) $subs), 'filled')),
            (array) (self::stored()[$key] ?? [])
        );
    }

    /** @return list<int> designations whose employees a lead can be assigned to; empty means every employee */
    public static function assigneeDesignationIds(): array
    {
        return array_values(array_unique(array_map('intval', array_filter((array) (self::stored()[self::ASSIGNEE_DESIGNATIONS] ?? []), 'is_numeric'))));
    }

    /**
     * Who the lead screens offer under "Assigned to": active employees of the chosen
     * designations, or every user while none are chosen.
     *
     * @return array<int, string>
     */
    public static function assignees(): array
    {
        $designationIds = self::assigneeDesignationIds();

        return User::query()
            ->when($designationIds, fn ($query) => $query->where('type', 'employee')->whereIn('designation_id', $designationIds)->active())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<string, string> */
    public static function subOptionsFor(string $key, ?string $parent): array
    {
        return blank($parent) ? [] : self::pairs(self::subOptions($key)[$parent] ?? []);
    }

    public static function save(string $key, array $value): void
    {
        Configuration::updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        app()->forgetInstance(self::MEMO);
    }

    /** @return array<string, mixed> every lead options key that has been saved, decoded */
    private static function stored(): array
    {
        if (! app()->bound(self::MEMO)) {
            $rows = Configuration::whereIn('key', [self::SOURCES, self::STATUSES, self::STATUS_ORDER, self::SUB_SOURCES, self::SUB_STATUSES, self::ASSIGNEE_DESIGNATIONS])
                ->pluck('value', 'key')
                ->map(fn ($json) => json_decode((string) $json, true))
                ->filter(fn ($decoded) => is_array($decoded))
                ->all();
            app()->instance(self::MEMO, $rows);
        }

        return app(self::MEMO);
    }

    /** accounts stored lists as value => value maps; accept those and plain lists alike. */
    private static function pairs(array $values): array
    {
        $values = array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), $values), 'filled')));

        return $values ? array_combine($values, $values) : [];
    }
}
