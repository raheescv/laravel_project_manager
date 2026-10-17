{{--
    Audit "sheet": one row per audit entry, one column per field — a spreadsheet
    read of a record's history. The Entry column (event · user · time) stays
    pinned while the fields scroll sideways, and the header stays pinned while
    the entries scroll down. Scoped under .adx; follows the settings theme
    (--bs-primary) and Bootstrap's light/dark attribute.
--}}
@props([
    'audits',
    'emptyMessage' => 'No audit entries found.',
    'hideColumns' => ['id', 'tenant_id', 'branch_id', 'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'],
    'showRecord' => false,
    'recordLabel' => 'Record',
])

@php
    $auditCollection = collect($audits)->values();

    $valuesOf = fn ($values) => is_array($values) ? $values : (array) ($values ?? []);

    $isBlank = fn ($value) => $value === null || $value === '' || $value === [];

    $formatValue = function ($value) use ($isBlank) {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($isBlank($value)) {
            return '—';
        }

        return (string) $value;
    };

    $eventMeta = fn ($event) => match ($event) {
        'created' => ['tone' => 'ok', 'icon' => 'fa-plus', 'label' => 'Created'],
        'updated' => ['tone' => 'acc', 'icon' => 'fa-pencil', 'label' => 'Updated'],
        'deleted' => ['tone' => 'bad', 'icon' => 'fa-trash-o', 'label' => 'Deleted'],
        'restored' => ['tone' => 'info', 'icon' => 'fa-undo', 'label' => 'Restored'],
        default => ['tone' => 'mut', 'icon' => 'fa-circle-o', 'label' => \Illuminate\Support\Str::headline((string) $event)],
    };

    $rows = $auditCollection->map(fn ($audit) => [
        'audit' => $audit,
        'old' => $valuesOf($audit->old_values),
        'new' => $valuesOf($audit->new_values),
    ]);

    $columns = $rows
        ->flatMap(fn ($row) => array_merge(array_keys($row['old']), array_keys($row['new'])))
        ->unique()
        ->reject(fn ($key) => in_array($key, $hideColumns, true))
        ->values();

    $blankColumns = $columns
        ->filter(fn ($column) => $rows->every(fn ($row) => $isBlank($row['old'][$column] ?? null) && $isBlank($row['new'][$column] ?? null)))
        ->values();

    $changedColumns = $columns
        ->filter(fn ($column) => $rows->contains(fn ($row) => array_key_exists($column, $row['old']) && array_key_exists($column, $row['new'])))
        ->values();

    $eventCounts = $auditCollection->countBy('event');
    $userCount = $auditCollection->map(fn ($audit) => $audit->user?->name ?? 'System')->unique()->count();
@endphp

<div class="adx">
    @if ($auditCollection->isEmpty())
        <div class="adx-empty">
            <span class="adx-empty-ic"><i class="fa fa-table"></i></span>
            <div>{{ $emptyMessage }}</div>
        </div>
    @else
        <div x-data="{ query: '', hideBlank: {{ $blankColumns->isNotEmpty() ? 'true' : 'false' }}, changedOnly: false, blank: @js($blankColumns), changed: @js($changedColumns), shows(column, label) { if (this.hideBlank && this.blank.includes(column)) { return false; } if (this.changedOnly && ! this.changed.includes(column)) { return false; } return this.query === '' || label.toLowerCase().includes(this.query.toLowerCase()) || column.includes(this.query.toLowerCase()); } }">
            <div class="adx-bar">
                <div class="adx-stats">
                    <span class="adx-stat"><b>{{ $auditCollection->count() }}</b> {{ \Illuminate\Support\Str::plural('entry', $auditCollection->count()) }}</span>
                    @foreach ($eventCounts as $event => $count)
                        @php $meta = $eventMeta($event); @endphp
                        <span class="adx-chip t-{{ $meta['tone'] }}"><i class="fa {{ $meta['icon'] }}"></i>{{ $count }} {{ \Illuminate\Support\Str::lower($meta['label']) }}</span>
                    @endforeach
                    <span class="adx-stat"><b>{{ $columns->count() }}</b> fields · <b>{{ $userCount }}</b> {{ \Illuminate\Support\Str::plural('user', $userCount) }}</span>
                </div>
                <div class="adx-tools">
                    <label class="adx-search">
                        <i class="fa fa-search"></i>
                        <input type="search" x-model.debounce.150ms="query" placeholder="Find a field…" aria-label="Find a field">
                    </label>
                    @if ($changedColumns->isNotEmpty())
                        <button type="button" class="adx-toggle" :class="{ on: changedOnly }" @click="changedOnly = ! changedOnly" :aria-pressed="changedOnly">
                            <i class="fa fa-exchange"></i>Changed only
                        </button>
                    @endif
                    @if ($blankColumns->isNotEmpty())
                        <button type="button" class="adx-toggle" :class="{ on: hideBlank }" @click="hideBlank = ! hideBlank" :aria-pressed="hideBlank">
                            <i class="fa" :class="hideBlank ? 'fa-eye-slash' : 'fa-eye'"></i>
                            <span x-text="hideBlank ? '{{ $blankColumns->count() }} empty hidden' : 'Hide empty'"></span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="adx-scroll">
                <table class="adx-tbl">
                    <thead>
                        <tr>
                            <th class="adx-pin">Entry</th>
                            @if ($showRecord)
                                <th class="adx-rec">{{ $recordLabel }}</th>
                            @endif
                            @foreach ($columns as $column)
                                @php $label = \Illuminate\Support\Str::headline($column); @endphp
                                <th x-show="shows(@js($column), @js($label))" @class(['adx-chg-h' => $changedColumns->contains($column)])>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $index => $row)
                            @php
                                $audit = $row['audit'];
                                $meta = $eventMeta($audit->event);
                                $userName = $audit->user?->name ?? 'System';
                            @endphp
                            <tr class="t-{{ $meta['tone'] }}">
                                <th class="adx-pin" scope="row">
                                    <div class="adx-entry">
                                        <span class="adx-idx">{{ $index + 1 }}</span>
                                        <div class="adx-entry-main">
                                            <div class="adx-entry-top">
                                                <span class="adx-ev"><i class="fa {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                                                <span class="adx-user" title="{{ $userName }}">{{ $userName }}</span>
                                            </div>
                                            <div class="adx-when" title="{{ $audit->created_at?->diffForHumans() }}">
                                                {{ $audit->created_at?->format('d M Y · h:i A') }}
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                @if ($showRecord)
                                    <td class="adx-rec"><span class="adx-recid">#{{ $audit->auditable_id }}</span></td>
                                @endif
                                @foreach ($columns as $column)
                                    @php
                                        $hasOld = array_key_exists($column, $row['old']);
                                        $hasNew = array_key_exists($column, $row['new']);
                                        $newText = $formatValue($row['new'][$column] ?? null);
                                        $oldText = $formatValue($row['old'][$column] ?? null);
                                    @endphp
                                    <td x-show="shows(@js($column), @js(\Illuminate\Support\Str::headline($column)))" @class(['adx-cell', 'is-chg' => $hasOld && $hasNew])>
                                        @if ($hasOld && $hasNew)
                                            <span class="adx-old" title="{{ $oldText }}">{{ $oldText }}</span>
                                            <span class="adx-new" title="{{ $newText }}">{{ $newText }}</span>
                                        @elseif ($hasNew)
                                            <span @class(['adx-val', 'is-blank' => $newText === '—']) title="{{ $newText }}">{{ $newText }}</span>
                                        @elseif ($hasOld)
                                            <span class="adx-gone" title="{{ $oldText }}">{{ $oldText }}</span>
                                        @else
                                            <span class="adx-none" aria-label="Not recorded">·</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="adx-legend">
                <span><i class="adx-sw sw-new"></i>New value</span>
                <span><i class="adx-sw sw-old"></i>Previous value</span>
                <span><span class="adx-none">·</span> Not part of this entry</span>
            </div>
        </div>
    @endif
</div>

@once
    <style>
        .adx {
            --a-acc: var(--bs-primary);
            --a-acc-d: color-mix(in srgb, var(--bs-primary), #000 15%);
            --a-tint: color-mix(in srgb, var(--bs-primary), transparent 91%);
            --a-tint-2: color-mix(in srgb, var(--bs-primary), transparent 96%);
            --a-sf: #ffffff;
            --a-sf-2: #f7f8fb;
            --a-sf-3: #eff2f7;
            --a-ink: var(--bs-emphasis-color);
            --a-mut: var(--bs-secondary-color);
            --a-ln: #e4e9f0;
            --a-ln-s: #eef1f6;
            --a-ok: var(--bs-success);
            --a-ok-rgb: var(--bs-success-rgb);
            --a-bad: var(--bs-danger);
            --a-bad-rgb: var(--bs-danger-rgb);
            --a-info: var(--bs-info);
            --a-info-rgb: var(--bs-info-rgb);
            color: var(--a-ink);
            font-size: 12px;
        }
        [data-bs-theme="dark"] .adx {
            --a-acc-d: color-mix(in srgb, var(--bs-primary), #fff 22%);
            --a-tint: color-mix(in srgb, var(--bs-primary), transparent 85%);
            --a-tint-2: color-mix(in srgb, var(--bs-primary), transparent 92%);
            --a-sf: #242a32;
            --a-sf-2: #2a313a;
            --a-sf-3: #313943;
            --a-ln: #39424d;
            --a-ln-s: #333b45;
        }

        .adx .t-ok { --tone: var(--a-ok); --tone-bg: rgba(var(--a-ok-rgb), .12); }
        .adx .t-acc { --tone: var(--a-acc-d); --tone-bg: var(--a-tint); }
        .adx .t-bad { --tone: var(--a-bad); --tone-bg: rgba(var(--a-bad-rgb), .11); }
        .adx .t-info { --tone: var(--a-info); --tone-bg: rgba(var(--a-info-rgb), .12); }
        .adx .t-mut { --tone: var(--a-mut); --tone-bg: var(--a-sf-3); }

        .adx-empty { padding: 34px 12px; text-align: center; color: var(--a-mut); }
        .adx-empty-ic {
            width: 42px; height: 42px; border-radius: 12px; margin: 0 auto 8px; font-size: 17px;
            display: flex; align-items: center; justify-content: center; background: var(--a-sf-3);
        }

        /* ── toolbar ─────────────────────────────────────────────── */
        .adx-bar {
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
            padding: 9px 12px; border-bottom: 1px solid var(--a-ln-s);
        }
        .adx-stats, .adx-tools { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; }
        .adx-stat { color: var(--a-mut); font-size: 11.5px; }
        .adx-stat b { color: var(--a-ink); font-weight: 800; }
        .adx-chip {
            display: inline-flex; align-items: center; gap: 5px; padding: 2px 8px; border-radius: 999px;
            font-size: 10.5px; font-weight: 750; background: var(--tone-bg); color: var(--tone);
        }
        .adx-chip .fa { font-size: 9px; }
        .adx-search {
            display: flex; align-items: center; gap: 6px; margin: 0; height: 28px; padding: 0 9px;
            border: 1px solid var(--a-ln); border-radius: 8px; background: var(--a-sf); color: var(--a-mut);
        }
        .adx-search:focus-within { border-color: var(--a-acc); box-shadow: 0 0 0 3px var(--a-tint); }
        .adx-search input { border: 0; outline: 0; background: transparent; color: var(--a-ink); font-size: 12px; width: 150px; }
        .adx-toggle {
            display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 10px; border-radius: 8px;
            border: 1px solid var(--a-ln); background: var(--a-sf); color: var(--a-mut); font-size: 11.5px; font-weight: 650;
        }
        .adx-toggle:hover { color: var(--a-ink); border-color: var(--a-mut); }
        .adx-toggle.on { background: var(--a-tint); border-color: transparent; color: var(--a-acc-d); }

        /* ── sheet ───────────────────────────────────────────────── */
        .adx-scroll { overflow: auto; max-height: 68vh; scrollbar-width: thin; }
        .adx-tbl { width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; }
        .adx-tbl thead th {
            position: sticky; top: 0; z-index: 2; background: var(--a-sf-2); color: var(--a-mut);
            font-size: 10px; font-weight: 800; letter-spacing: .6px; text-transform: uppercase; white-space: nowrap;
            padding: 9px 12px; border-bottom: 1px solid var(--a-ln);
        }
        .adx-tbl thead th.adx-chg-h { color: var(--a-acc-d); }
        .adx-tbl tbody td, .adx-tbl tbody th {
            padding: 8px 12px; border-bottom: 1px solid var(--a-ln-s); vertical-align: top; background: var(--a-sf);
        }
        .adx-tbl tbody tr:last-child > * { border-bottom: 0; }
        .adx-tbl tbody tr:hover > * { background: color-mix(in srgb, var(--a-sf), var(--bs-primary) 4%); }

        .adx-pin { position: sticky; left: 0; z-index: 1; min-width: 210px; border-right: 1px solid var(--a-ln); }
        .adx-tbl thead th.adx-pin { z-index: 3; }
        .adx-tbl tbody th.adx-pin { box-shadow: inset 3px 0 0 var(--tone); font-weight: normal; }
        .adx-entry { display: flex; gap: 9px; align-items: flex-start; }
        .adx-idx {
            flex: 0 0 auto; min-width: 22px; height: 22px; border-radius: 7px; padding: 0 5px;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--a-sf-3); color: var(--a-mut); font-size: 10.5px; font-weight: 800; font-variant-numeric: tabular-nums;
        }
        .adx-entry-main { min-width: 0; }
        .adx-entry-top { display: flex; align-items: center; gap: 7px; }
        .adx-ev {
            display: inline-flex; align-items: center; gap: 4px; padding: 1px 7px; border-radius: 6px;
            font-size: 10px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase;
            background: var(--tone-bg); color: var(--tone);
        }
        .adx-ev .fa { font-size: 9px; }
        .adx-user { font-weight: 700; color: var(--a-ink); max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .adx-when { color: var(--a-mut); font-size: 11px; margin-top: 2px; white-space: nowrap; font-variant-numeric: tabular-nums; }

        .adx-rec { white-space: nowrap; }
        .adx-recid { font-weight: 750; color: var(--a-acc-d); font-variant-numeric: tabular-nums; }

        .adx-cell { min-width: 96px; max-width: 240px; }
        .adx-cell.is-chg { background: color-mix(in srgb, var(--a-sf), var(--bs-warning) 7%); }
        .adx-cell > span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .adx-val { color: var(--a-ink); font-weight: 550; }
        .adx-val.is-blank { color: var(--a-mut); opacity: .55; }
        .adx-old { color: var(--a-bad); text-decoration: line-through; opacity: .75; font-size: 11px; }
        .adx-new { color: var(--a-ok); font-weight: 750; }
        .adx-gone { color: var(--a-bad); text-decoration: line-through; opacity: .8; }
        .adx-none { color: var(--a-mut); opacity: .45; font-weight: 800; }

        .adx-legend {
            display: flex; flex-wrap: wrap; gap: 14px; padding: 7px 12px; border-top: 1px solid var(--a-ln-s);
            background: var(--a-sf-2); color: var(--a-mut); font-size: 10.5px;
        }
        .adx-legend > span { display: inline-flex; align-items: center; gap: 5px; }
        .adx-sw { width: 9px; height: 9px; border-radius: 3px; display: inline-block; }
        .adx-sw.sw-new { background: var(--a-ok); }
        .adx-sw.sw-old { background: var(--a-bad); opacity: .75; }

        @media (max-width: 575.98px) {
            .adx-tools, .adx-search { width: 100%; }
            .adx-search input { width: 100%; }
            .adx-pin { position: static; min-width: 180px; }
        }
    </style>
@endonce
