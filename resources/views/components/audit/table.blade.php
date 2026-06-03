@props([
    'audits',
    'view' => 'timeline',
    'emptyMessage' => 'No audit entries found.',
    'hideColumns' => [],
])

@php
    $auditCollection = $audits instanceof \Illuminate\Support\Collection ? $audits : collect($audits);
    $auditCollection = $auditCollection->values();

    $palette = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];

    $formatValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }
        if ($value === null || $value === '') {
            return '—';
        }
        return (string) $value;
    };

    $eventMeta = fn ($event) => match ($event) {
        'created' => ['badge' => 'success', 'icon' => 'fa-plus-circle', 'label' => 'Created'],
        'updated' => ['badge' => 'primary', 'icon' => 'fa-pencil-square-o', 'label' => 'Updated'],
        'deleted' => ['badge' => 'danger', 'icon' => 'fa-trash-o', 'label' => 'Deleted'],
        'restored' => ['badge' => 'info', 'icon' => 'fa-undo', 'label' => 'Restored'],
        default => ['badge' => 'secondary', 'icon' => 'fa-circle', 'label' => ucfirst((string) $event)],
    };

    $titleize = fn ($k) => \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', $k));

    // For pivot view
    $allKeys = collect();
    foreach ($auditCollection as $audit) {
        $oldValues = is_array($audit->old_values ?? null) ? $audit->old_values : (array) ($audit->old_values ?? []);
        $newValues = is_array($audit->new_values ?? null) ? $audit->new_values : (array) ($audit->new_values ?? []);
        $allKeys = $allKeys->merge(array_keys($oldValues))->merge(array_keys($newValues));
    }
    $allKeys = $allKeys->unique()->reject(fn ($k) => in_array($k, $hideColumns, true))->values();
@endphp

<div class="audit-component">
    @if ($auditCollection->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="fa fa-history fa-3x opacity-25 d-block mb-2"></i>
            <small>{{ $emptyMessage }}</small>
        </div>
    @elseif ($view === 'table')
        {{-- Pivot Table View --}}
        @php
            // Pre-compute per-column change counts
            $columnChanges = [];
            foreach ($auditCollection as $cIdx => $audit) {
                $oldV = is_array($audit->old_values ?? null) ? $audit->old_values : (array) ($audit->old_values ?? []);
                $newV = is_array($audit->new_values ?? null) ? $audit->new_values : (array) ($audit->new_values ?? []);
                $count = 0;
                foreach ($allKeys as $k) {
                    if (array_key_exists($k, $oldV) && array_key_exists($k, $newV) && $oldV[$k] !== $newV[$k]) {
                        $count++;
                    } elseif (! array_key_exists($k, $oldV) && array_key_exists($k, $newV)) {
                        $count++;
                    }
                }
                $columnChanges[$cIdx] = $count;
            }
        @endphp
        <div class="audit-pivot-list">
            <div class="audit-pivot-scroll"
                 x-data="{ scrolled: false }"
                 x-init="$el.addEventListener('scroll', () => scrolled = $el.scrollLeft > 0)"
                 :class="scrolled ? 'is-scrolled' : ''">
                <table class="audit-pivot-table mb-0">
                    <thead>
                        <tr>
                            <th class="audit-pivot-field-col audit-pivot-sticky">
                                <div class="audit-pivot-field-header">
                                    <i class="fa fa-list-alt me-1"></i>Field
                                </div>
                            </th>
                            @foreach ($auditCollection as $index => $audit)
                                @php
                                    $userName = $audit->user?->name ?? 'System';
                                    $userInitial = strtoupper(mb_substr($userName, 0, 1));
                                    $createdAtAbs = $audit->created_at instanceof \Carbon\CarbonInterface
                                        ? $audit->created_at->format('M d, Y H:i')
                                        : (string) $audit->created_at;
                                    $createdAtRel = $audit->created_at instanceof \Carbon\CarbonInterface
                                        ? $audit->created_at->diffForHumans()
                                        : '';
                                    $meta = $eventMeta($audit->event);
                                    $color = $palette[$index % count($palette)];
                                    $changeCount = $columnChanges[$index] ?? 0;
                                @endphp
                                <th class="audit-pivot-change-col audit-col-event-{{ $meta['badge'] }}">
                                    <div class="audit-col-card">
                                        <div class="audit-col-card-top">
                                            <span class="audit-col-index">#{{ $index + 1 }}</span>
                                            <span class="badge bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }} fw-semibold">
                                                <i class="fa {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                                            </span>
                                        </div>
                                        <div class="audit-col-card-user">
                                            <span class="audit-user-avatar bg-{{ $color }}-subtle text-{{ $color }}">{{ $userInitial }}</span>
                                            <span class="audit-col-username">{{ $userName }}</span>
                                        </div>
                                        <div class="audit-col-card-time" title="{{ $createdAtAbs }}">
                                            <i class="fa fa-clock-o text-muted me-1"></i>
                                            <span>{{ $createdAtRel ?: $createdAtAbs }}</span>
                                        </div>
                                        @if ($changeCount > 0)
                                            <div class="audit-col-card-footer">
                                                <span class="audit-col-change-pill">
                                                    <i class="fa fa-circle-o-notch me-1"></i>{{ $changeCount }} {{ \Illuminate\Support\Str::plural('change', $changeCount) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($allKeys as $rowIdx => $key)
                            <tr class="audit-pivot-row">
                                <th class="audit-pivot-field-col audit-pivot-sticky">
                                    <div class="audit-pivot-field-label">
                                        <span class="audit-field-bullet"></span>
                                        <span>{{ $titleize($key) }}</span>
                                    </div>
                                </th>
                                @foreach ($auditCollection as $audit)
                                    @php
                                        $oldValues = is_array($audit->old_values ?? null) ? $audit->old_values : (array) ($audit->old_values ?? []);
                                        $newValues = is_array($audit->new_values ?? null) ? $audit->new_values : (array) ($audit->new_values ?? []);
                                        $hasOld = array_key_exists($key, $oldValues);
                                        $hasNew = array_key_exists($key, $newValues);
                                        $oldVal = $oldValues[$key] ?? null;
                                        $newVal = $newValues[$key] ?? null;
                                        $changed = $hasOld && $hasNew && $oldVal !== $newVal;
                                        $isEmpty = ! $hasOld && ! $hasNew;
                                    @endphp
                                    <td class="audit-pivot-change-col {{ $isEmpty ? 'audit-cell-empty' : '' }} {{ $changed ? 'audit-cell-changed' : '' }}">
                                        @if ($changed)
                                            <div class="audit-cell-diff">
                                                <span class="audit-pill audit-pill-old">{{ $formatValue($oldVal) }}</span>
                                                <i class="fa fa-long-arrow-right audit-cell-arrow"></i>
                                                <span class="audit-pill audit-pill-new">{{ $formatValue($newVal) }}</span>
                                            </div>
                                        @elseif ($hasNew && $audit->event !== 'deleted')
                                            <span class="audit-pill audit-pill-new">{{ $formatValue($newVal) }}</span>
                                        @elseif ($hasOld)
                                            <span class="audit-pill audit-pill-old">{{ $formatValue($oldVal) }}</span>
                                        @else
                                            <span class="audit-cell-dash">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $auditCollection->count() + 1 }}" class="text-center text-muted small py-3">No field changes recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- Timeline View --}}
        <ol class="audit-timeline list-unstyled mb-0">
            @foreach ($auditCollection as $index => $audit)
                @php
                    $meta = $eventMeta($audit->event);
                    $userName = $audit->user?->name ?? 'System';
                    $userInitial = strtoupper(mb_substr($userName, 0, 1));
                    $userColor = $palette[$index % count($palette)];
                    $createdAtAbs = $audit->created_at instanceof \Carbon\CarbonInterface
                        ? $audit->created_at->format('Y-m-d H:i:s')
                        : (string) $audit->created_at;
                    $createdAtRel = $audit->created_at instanceof \Carbon\CarbonInterface
                        ? $audit->created_at->diffForHumans()
                        : $createdAtAbs;

                    $oldValues = is_array($audit->old_values ?? null) ? $audit->old_values : (array) ($audit->old_values ?? []);
                    $newValues = is_array($audit->new_values ?? null) ? $audit->new_values : (array) ($audit->new_values ?? []);
                    $keys = collect(array_keys($oldValues + $newValues))
                        ->reject(fn ($k) => in_array($k, $hideColumns, true))
                        ->values();
                @endphp
                <li class="audit-timeline-item" x-data="{ open: true }">
                    <span class="audit-timeline-dot bg-{{ $meta['badge'] }}-subtle text-{{ $meta['badge'] }}">
                        <i class="fa {{ $meta['icon'] }}"></i>
                    </span>
                    <div class="audit-timeline-card">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="badge bg-{{ $meta['badge'] }} text-uppercase">
                                <i class="fa {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}
                            </span>
                            <span class="audit-timeline-user">
                                <span class="audit-user-avatar bg-{{ $userColor }}-subtle text-{{ $userColor }} me-1">{{ $userInitial }}</span>
                                <span class="fw-semibold">{{ $userName }}</span>
                            </span>
                            <span class="text-muted small ms-auto" title="{{ $createdAtAbs }}">
                                <i class="fa fa-clock-o me-1"></i>{{ $createdAtRel }}
                                <span class="d-none d-md-inline text-muted ms-1">&middot; {{ $createdAtAbs }}</span>
                            </span>
                            @if ($keys->count() > 0)
                                <button type="button" class="btn btn-sm btn-link p-0 ms-1 text-decoration-none" @click="open = !open">
                                    <i class="fa" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            @endif
                        </div>
                        @if ($keys->isEmpty())
                            <div class="text-muted small fst-italic">No field changes recorded.</div>
                        @else
                            <div class="audit-diff-list" x-show="open">
                                @foreach ($keys as $key)
                                    @php
                                        $hasOld = array_key_exists($key, $oldValues);
                                        $hasNew = array_key_exists($key, $newValues);
                                        $oldVal = $oldValues[$key] ?? null;
                                        $newVal = $newValues[$key] ?? null;
                                        $changed = $hasOld && $hasNew && $oldVal !== $newVal;
                                        $oldStr = $formatValue($oldVal);
                                        $newStr = $formatValue($newVal);
                                        $oldLong = mb_strlen($oldStr) > 80;
                                        $newLong = mb_strlen($newStr) > 80;
                                    @endphp
                                    <div class="audit-diff-row" x-data="{ expanded: false }">
                                        <div class="audit-diff-field">{{ $titleize($key) }}</div>
                                        <div class="audit-diff-values">
                                            @if ($changed)
                                                <span class="audit-pill audit-pill-old" :class="expanded ? '' : 'audit-pill-truncate'">{{ $oldStr }}</span>
                                                <i class="fa fa-long-arrow-right text-muted mx-1"></i>
                                                <span class="audit-pill audit-pill-new" :class="expanded ? '' : 'audit-pill-truncate'">{{ $newStr }}</span>
                                            @elseif ($hasNew && $audit->event !== 'deleted')
                                                <span class="audit-pill audit-pill-new" :class="expanded ? '' : 'audit-pill-truncate'">{{ $newStr }}</span>
                                            @elseif ($hasOld)
                                                <span class="audit-pill audit-pill-old" :class="expanded ? '' : 'audit-pill-truncate'">{{ $oldStr }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                            @if ($oldLong || $newLong)
                                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 text-decoration-none small" @click="expanded = !expanded">
                                                    <span x-show="!expanded">Show more</span>
                                                    <span x-show="expanded" x-cloak>Show less</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>

@once
    @push('styles')
        <style>
            [x-cloak] { display: none !important; }

            /* Pivot Table — modern design */
            .audit-component .audit-pivot-list {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                overflow: hidden;
            }
            .audit-component .audit-pivot-scroll {
                overflow-x: auto;
                overflow-y: visible;
                position: relative;
                max-width: 100%;
                scrollbar-width: thin;
                scrollbar-color: #cbd5e1 transparent;
            }
            .audit-component .audit-pivot-scroll::-webkit-scrollbar { height: 8px; }
            .audit-component .audit-pivot-scroll::-webkit-scrollbar-track { background: transparent; }
            .audit-component .audit-pivot-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
            .audit-component .audit-pivot-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
            .audit-component .audit-pivot-table {
                font-size: 0.82rem;
                border-collapse: separate;
                border-spacing: 0;
                width: 100%;
                min-width: 100%;
                color: #0f172a;
            }

            /* Header cells */
            .audit-component .audit-pivot-table thead th {
                background: #f8fafc;
                vertical-align: top;
                padding: 12px;
                border-bottom: 1px solid #e2e8f0;
                white-space: nowrap;
                font-weight: 600;
            }
            .audit-component .audit-pivot-field-header {
                font-size: 0.75rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #64748b;
                padding: 6px 4px;
            }
            .audit-component .audit-col-card {
                display: flex;
                flex-direction: column;
                gap: 6px;
                padding: 4px;
                min-width: 200px;
            }
            .audit-component .audit-col-card-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
            }
            .audit-component .audit-col-index {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 28px;
                height: 22px;
                padding: 0 6px;
                border-radius: 6px;
                background: #f1f5f9;
                color: #475569;
                font-size: 0.7rem;
                font-weight: 700;
                letter-spacing: 0.3px;
            }
            .audit-component .audit-col-card-user {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 0.78rem;
            }
            .audit-component .audit-col-username {
                font-weight: 600;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 140px;
            }
            .audit-component .audit-col-card-time {
                font-size: 0.72rem;
                color: #64748b;
                font-weight: 500;
            }
            .audit-component .audit-col-card-footer {
                padding-top: 4px;
                border-top: 1px dashed #e2e8f0;
            }
            .audit-component .audit-col-change-pill {
                display: inline-flex;
                align-items: center;
                font-size: 0.7rem;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 10px;
                background: #eef2ff;
                color: #4338ca;
            }

            /* Event accent (top border per column) */
            .audit-component .audit-col-event-success { box-shadow: inset 0 3px 0 #10b981; }
            .audit-component .audit-col-event-primary { box-shadow: inset 0 3px 0 #3b82f6; }
            .audit-component .audit-col-event-danger { box-shadow: inset 0 3px 0 #ef4444; }
            .audit-component .audit-col-event-info { box-shadow: inset 0 3px 0 #06b6d4; }
            .audit-component .audit-col-event-secondary { box-shadow: inset 0 3px 0 #64748b; }

            /* Field column (rows) */
            .audit-component .audit-pivot-table tbody th {
                background: #fafbfc;
                font-weight: 600;
                color: #334155;
                padding: 10px 14px;
                white-space: nowrap;
                vertical-align: middle;
                border-bottom: 1px solid #f1f5f9;
            }
            .audit-component .audit-pivot-field-label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 0.82rem;
            }
            .audit-component .audit-field-bullet {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #94a3b8;
                flex-shrink: 0;
            }

            /* Body cells */
            .audit-component .audit-pivot-table tbody td {
                padding: 10px 12px;
                vertical-align: middle;
                word-break: break-word;
                border-bottom: 1px solid #f1f5f9;
                background: #fff;
                transition: background-color .12s ease;
            }
            .audit-component .audit-pivot-row:hover tbody th,
            .audit-component .audit-pivot-row:hover td { background: #f8fafc; }
            .audit-component .audit-pivot-row:last-child th,
            .audit-component .audit-pivot-row:last-child td { border-bottom: 0; }
            .audit-component .audit-cell-changed { background: #fefce8; }
            .audit-component .audit-pivot-row:hover .audit-cell-changed { background: #fef9c3; }
            .audit-component .audit-cell-empty { background: #fafbfc; }
            .audit-component .audit-cell-dash { color: #cbd5e1; font-weight: 600; }
            .audit-component .audit-cell-diff {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }
            .audit-component .audit-cell-arrow {
                color: #94a3b8;
                font-size: 0.85rem;
            }

            /* Sticky first column */
            .audit-component .audit-pivot-sticky {
                position: sticky;
                left: 0;
                z-index: 2;
            }
            .audit-component .audit-pivot-sticky::after {
                content: '';
                position: absolute;
                top: 0;
                right: -8px;
                width: 8px;
                height: 100%;
                pointer-events: none;
                background: linear-gradient(to right, rgba(15,23,42,0.06), transparent);
                opacity: 0;
                transition: opacity .15s ease;
            }
            .audit-component .audit-pivot-scroll.is-scrolled .audit-pivot-sticky::after { opacity: 1; }
            .audit-component thead .audit-pivot-sticky { z-index: 4; background: #f8fafc !important; }
            .audit-component tbody .audit-pivot-sticky { background: #fafbfc !important; }
            .audit-component .audit-pivot-row:hover tbody.audit-pivot-sticky,
            .audit-component .audit-pivot-row:hover .audit-pivot-sticky { background: #f1f5f9 !important; }
            .audit-component .audit-pivot-field-col { min-width: 200px; max-width: 260px; }
            .audit-component .audit-pivot-change-col { min-width: 220px; }

            /* Shared avatar */
            .audit-user-avatar {
                width: 22px;
                height: 22px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-weight: 700;
                font-size: 0.68rem;
            }

            /* Timeline */
            .audit-timeline {
                position: relative;
                padding-left: 38px;
                margin: 0;
            }
            .audit-timeline::before {
                content: '';
                position: absolute;
                left: 17px;
                top: 8px;
                bottom: 8px;
                width: 2px;
                background: linear-gradient(to bottom, #e2e8f0, #e2e8f0 80%, transparent);
            }
            .audit-timeline-item {
                position: relative;
                margin-bottom: 16px;
            }
            .audit-timeline-item:last-child { margin-bottom: 0; }
            .audit-timeline-dot {
                position: absolute;
                left: -38px;
                top: 8px;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1rem;
                box-shadow: 0 0 0 4px #fff;
                z-index: 1;
            }
            .audit-timeline-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 14px 16px;
                transition: box-shadow .15s ease, border-color .15s ease;
            }
            .audit-timeline-card:hover {
                border-color: #cbd5e1;
                box-shadow: 0 4px 12px -4px rgba(15, 23, 42, 0.08);
            }
            .audit-timeline-user {
                display: inline-flex;
                align-items: center;
                font-size: 0.85rem;
            }
            .audit-diff-list {
                border-top: 1px dashed #e2e8f0;
                padding-top: 10px;
                margin-top: 4px;
            }
            .audit-diff-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                padding: 6px 0;
                border-bottom: 1px dashed #f1f5f9;
            }
            .audit-diff-row:last-child { border-bottom: 0; }
            .audit-diff-field {
                min-width: 160px;
                font-weight: 600;
                color: #475569;
                font-size: 0.82rem;
            }
            .audit-diff-values {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 4px;
                flex: 1;
                min-width: 0;
            }
            .audit-pill {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 6px;
                font-size: 0.82rem;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                max-width: 100%;
                word-break: break-word;
            }
            .audit-pill-truncate {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 320px;
            }
            .audit-pill-old {
                background: #fef2f2;
                color: #b91c1c;
                text-decoration: line-through;
            }
            .audit-pill-new {
                background: #ecfdf5;
                color: #047857;
                font-weight: 600;
            }

            @media (max-width: 575.98px) {
                .audit-component .audit-pivot-table { font-size: 0.76rem; }
                .audit-component .audit-pivot-field-col { min-width: 150px; max-width: 180px; }
                .audit-component .audit-pivot-change-col { min-width: 180px; }
                .audit-component .audit-col-card { min-width: 170px; }
                .audit-component .audit-col-username { max-width: 100px; }
                .audit-timeline { padding-left: 30px; }
                .audit-timeline::before { left: 13px; }
                .audit-timeline-dot { left: -30px; width: 28px; height: 28px; font-size: 0.8rem; }
                .audit-diff-field { min-width: 100%; }
                .audit-pill-truncate { max-width: 220px; }
            }
        </style>
    @endpush
@endonce
