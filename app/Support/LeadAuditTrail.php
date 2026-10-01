<?php

namespace App\Support;

use App\Models\Country;
use App\Models\PropertyGroup;
use App\Models\PropertyLead;
use App\Models\PropertyType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

/**
 * A lead's audit log shaped for reading: one row per change, one column per
 * field that ever changed, ids resolved to names and values in the system
 * formats. Bookkeeping columns are dropped, and a value that only changed
 * shape ("18:00" vs "18:00:00", "1500" vs "1500.00") does not count as a change.
 */
class LeadAuditTrail
{
    /** Readable header for every field worth showing, in display order. */
    public const COLUMNS = [
        'name' => 'Name',
        'mobile' => 'Mobile',
        'email' => 'Email',
        'type' => 'Type',
        'status' => 'Status',
        'sub_status' => 'Sub status',
        'source' => 'Source',
        'sub_source' => 'Sub source',
        'assigned_to' => 'Assigned to',
        'assign_date' => 'Assign date',
        'reassigned_at' => 'Reassigned',
        'meeting_date' => 'Meeting date',
        'meeting_time' => 'Meeting time',
        'location' => 'Location',
        'property_group_id' => 'Project / group',
        'property_type_id' => 'Property type',
        'rental_type' => 'Rental type',
        'budget_min' => 'Budget min',
        'budget_max' => 'Budget max',
        'company_name' => 'Company',
        'company_contact_person' => 'Contact person',
        'company_contact_no' => 'Company no',
        'country_id' => 'Nationality',
        'nationality' => 'Nationality (text)',
        'remarks' => 'Notes',
    ];

    /**
     * @return array{columns: array<string, string>, rows: list<array{event: string, at: ?Carbon, user: ?string, cells: array<string, array{old: ?string, new: ?string}>}>}
     */
    public static function for(int $leadId): array
    {
        $audits = Audit::query()
            ->where('auditable_type', PropertyLead::class)
            ->where('auditable_id', $leadId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'event', 'old_values', 'new_values', 'created_at']);

        $names = self::lookups($audits);

        $rows = [];
        $used = [];
        foreach ($audits as $audit) {
            $old = (array) $audit->old_values;
            $new = (array) $audit->new_values;
            $cells = [];

            foreach (array_keys(self::COLUMNS) as $field) {
                if (! array_key_exists($field, $old) && ! array_key_exists($field, $new)) {
                    continue;
                }

                if ($field === 'remarks') {
                    $cell = self::notesCell($old['remarks'] ?? null, $new['remarks'] ?? null);
                } else {
                    $before = self::display($field, $old[$field] ?? null, $names);
                    $after = self::display($field, $new[$field] ?? null, $names);
                    $cell = $before === $after ? null : ['old' => $before, 'new' => $after];
                }

                if ($cell && ($cell['old'] !== null || $cell['new'] !== null)) {
                    $cells[$field] = $cell;
                    $used[$field] = true;
                }
            }

            // An update that only touched bookkeeping columns tells the reader nothing.
            if (! $cells && $audit->event !== 'created') {
                continue;
            }

            $rows[] = [
                'event' => $audit->event,
                'at' => $audit->created_at,
                'user' => $names['users'][$audit->user_id] ?? null,
                'cells' => $cells,
            ];
        }

        return [
            'columns' => array_intersect_key(self::COLUMNS, $used),
            'rows' => $rows,
        ];
    }

    /** Names for every id the audits mention, fetched once per table. */
    private static function lookups(Collection $audits): array
    {
        $ids = ['users' => [], 'property_group_id' => [], 'property_type_id' => [], 'country_id' => []];
        foreach ($audits as $audit) {
            $ids['users'][] = $audit->user_id;
            foreach ([(array) $audit->old_values, (array) $audit->new_values] as $values) {
                $ids['users'][] = $values['assigned_to'] ?? null;
                foreach (['property_group_id', 'property_type_id', 'country_id'] as $field) {
                    $ids[$field][] = $values[$field] ?? null;
                }
            }
        }
        $ids = array_map(fn (array $list) => array_values(array_unique(array_filter($list, 'filled'))), $ids);

        return [
            'users' => User::withoutGlobalScopes()->whereIn('id', $ids['users'])->pluck('name', 'id')->all(),
            'property_group_id' => PropertyGroup::withoutGlobalScopes()->whereIn('id', $ids['property_group_id'])->pluck('name', 'id')->all(),
            'property_type_id' => PropertyType::withoutGlobalScopes()->whereIn('id', $ids['property_type_id'])->pluck('name', 'id')->all(),
            'country_id' => Country::whereIn('id', $ids['country_id'])->pluck('name', 'id')->all(),
        ];
    }

    private static function display(string $field, mixed $value, array $names): ?string
    {
        if (blank($value)) {
            return null;
        }

        return match ($field) {
            'assigned_to' => $names['users'][$value] ?? "User #{$value}",
            'property_group_id', 'property_type_id', 'country_id' => $names[$field][$value] ?? "#{$value}",
            'type' => $value === 'Rentout' ? 'Rent out' : (string) $value,
            'status' => ($status = LeadPipeline::canonical($value)) === LeadPipeline::UNMAPPED ? trim((string) $value) : $status,
            'assign_date', 'meeting_date' => systemDate($value),
            'reassigned_at' => systemDateTime($value),
            'meeting_time' => Carbon::parse($value)->format('h:i A'),
            'budget_min', 'budget_max' => currency($value),
            default => trim((string) $value),
        };
    }

    /** Notes live in one JSON list, so the change is which notes appeared or went. */
    private static function notesCell(mixed $old, mixed $new): ?array
    {
        $before = self::notes($old);
        $after = self::notes($new);

        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));
        if (! $added && ! $removed) {
            return null;
        }

        return [
            'old' => $removed ? implode("\n", $removed) : null,
            'new' => $added ? implode("\n", $added) : null,
        ];
    }

    /** @return list<string> note texts, whatever shape the audit stored them in */
    private static function notes(mixed $value): array
    {
        $list = is_array($value) ? $value : json_decode((string) $value, true);

        return array_values(array_filter(array_map(
            fn ($note) => trim((string) (is_array($note) ? ($note['note'] ?? '') : $note)),
            is_array($list) ? $list : []
        ), 'filled'));
    }
}
