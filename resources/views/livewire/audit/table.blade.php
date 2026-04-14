<div>
    @php
        // Collect all unique field keys across all audits for the dynamic columns table
        $allFieldKeys = collect();
        foreach ($audits as $audit) {
            $allFieldKeys = $allFieldKeys->merge(array_keys($audit->new_values ?? []));
            $allFieldKeys = $allFieldKeys->merge(array_keys($audit->old_values ?? []));
        }
        $allFieldKeys = $allFieldKeys->unique()->values();
    @endphp

    {{-- ============================================================ --}}
    {{-- SECTION 1: Audit History Header + Overview Table              --}}
    {{-- ============================================================ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between mb-1">
                <div>
                    <p class="text-muted small mb-0">
                        <b>{{ class_basename($audits->first()->auditable_type ?? '') }} #{{ $audits->first()->auditable_id ?? '' }}</b>
                    </p>
                    <p class="text-muted small mb-0">
                        <b>{{ $audits->first()->auditable_type ?? '' }}</b>
                    </p>
                </div>
            </div>
        </div>

        @if ($audits->count() > 0)
            <div class="audit-table-wrapper">
                <table class="table table-bordered table-hover mb-0 align-middle audit-sticky-table">
                    <thead>
                        <tr>
                            <th class="audit-sticky-col audit-col-1 text-nowrap px-3 py-2 text-muted fw-semibold">Date</th>
                            <th class="audit-sticky-col audit-col-2 text-nowrap px-3 py-2 text-muted fw-semibold">User</th>
                            <th class="audit-sticky-col audit-col-3 text-nowrap px-3 py-2 text-muted fw-semibold">Event</th>
                            @foreach ($allFieldKeys as $fieldKey)
                                <th class="text-nowrap px-3 py-2 text-muted fw-semibold text-uppercase" style="min-width: 120px;">
                                    {{ strtoupper(str_replace(['_', '-'], ' ', $fieldKey)) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($audits as $item)
                            @php
                                $displayValues = $item->event === 'deleted'
                                    ? ($item->old_values ?? [])
                                    : ($item->new_values ?? []);
                            @endphp
                            <tr>
                                <td class="audit-sticky-col audit-col-1 px-3 py-2 text-nowrap fw-medium">
                                    {{ $item->created_at->format('d M Y, h:i A') }}
                                </td>
                                <td class="audit-sticky-col audit-col-2 px-3 py-2 text-nowrap">
                                    {{ $item->user?->name ?? 'System' }}
                                </td>
                                <td class="audit-sticky-col audit-col-3 px-3 py-2">
                                    <span class="badge rounded-pill px-2 py-1
                                        @if ($item->event === 'created') bg-success
                                        @elseif($item->event === 'updated') bg-primary
                                        @elseif($item->event === 'deleted') bg-danger
                                        @else bg-secondary @endif">
                                        {{ ucfirst($item->event) }}
                                    </span>
                                </td>
                                @foreach ($allFieldKeys as $fieldKey)
                                    @php
                                        $hasValue = array_key_exists($fieldKey, $displayValues);
                                        $value = $displayValues[$fieldKey] ?? null;
                                        $wasChanged = array_key_exists($fieldKey, $item->new_values ?? [])
                                            || array_key_exists($fieldKey, $item->old_values ?? []);
                                    @endphp
                                    <td class="px-3 py-2 {{ $wasChanged && $item->event === 'updated' ? 'table-warning' : '' }}">
                                        @if ($hasValue)
                                            <span class="{{ $item->event === 'deleted' ? 'text-danger' : '' }}">
                                                {{ is_array($value) ? json_encode($value) : $value }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-center py-5">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                    <i class="fa fa-history text-muted" style="font-size: 2rem;"></i>
                </div>
                <h5 class="text-muted mb-2">No Audit Records Found</h5>
                <p class="text-muted mb-0 small">No changes have been recorded for this item yet.</p>
            </div>
        @endif
    </div>

    {{-- ============================================================ --}}
    {{-- SECTION 2: Detailed Changes (Column / Old Value / New Value) --}}
    {{-- ============================================================ --}}
    @if ($audits->count() > 0)
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Detailed Changes</h5>
                    <span class="text-muted small">Showing {{ $audits->count() }} audit record(s)</span>
                </div>

                @foreach ($audits as $index => $item)
                    @php
                        $allKeys = collect(array_keys($item->old_values ?? []))
                            ->merge(array_keys($item->new_values ?? []))
                            ->unique();
                    @endphp

                    @if ($allKeys->count() > 0)
                        <div class="card border mb-4">
                            <div class="card-header bg-light d-flex align-items-center justify-content-between py-2 px-3">
                                <h6 class="mb-0 fw-semibold">Audit Report {{ $index + 1 }}</h6>
                                <span class="text-muted small">
                                    {{ $item->created_at->format('d M Y, h:i A') }}
                                    &middot; {{ $item->user?->name ?? 'System' }}
                                    &middot; <span class="
                                        @if ($item->event === 'created') text-success
                                        @elseif($item->event === 'updated') text-primary
                                        @elseif($item->event === 'deleted') text-danger
                                        @else text-secondary @endif fw-semibold">{{ $item->event }}</span>
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0 align-middle" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr class="bg-light">
                                            <th class="px-3 py-2 fw-semibold text-muted" style="width: 30%;">Column</th>
                                            <th class="px-3 py-2 fw-semibold text-muted" style="width: 35%;">Old Value</th>
                                            <th class="px-3 py-2 fw-semibold text-muted" style="width: 35%;">New Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($allKeys as $key)
                                            @php
                                                $oldVal = $item->old_values[$key] ?? null;
                                                $newVal = $item->new_values[$key] ?? null;
                                                $displayKey = str_replace(['_', '-'], ' ', $key);
                                                $changed = $oldVal !== $newVal;
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2 fw-medium">{{ $displayKey }}</td>
                                                <td class="px-3 py-2 {{ $changed && $oldVal !== null ? 'text-danger' : '' }}">
                                                    @if ($oldVal !== null)
                                                        {{ is_array($oldVal) ? json_encode($oldVal) : $oldVal }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 {{ $changed && $newVal !== null ? 'text-success fw-semibold' : '' }}">
                                                    @if ($newVal !== null)
                                                        {{ is_array($newVal) ? json_encode($newVal) : $newVal }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <style>
        /* ── Scrollable wrapper ── */
        .audit-table-wrapper {
            overflow-x: auto;
            overflow-y: visible;
            position: relative;
            max-width: 100%;
        }

        .audit-sticky-table {
            font-size: 0.85rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        /* ── Sticky header row (top) ── */
        .audit-sticky-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #f0f1f3 !important;
            border-bottom: 2px solid #dee2e6;
        }

        /* ── Sticky columns (left) — both th and td ── */
        .audit-sticky-col {
            position: sticky;
            z-index: 2;
            background: #fff;
        }

        /* thead sticky cols: sticky both top+left → highest z-index */
        thead .audit-sticky-col {
            z-index: 6 !important;
            background: #f0f1f3 !important;
        }

        /* ── Column positions ── */
        .audit-col-1 {
            left: 0;
            min-width: 175px;
            max-width: 175px;
        }
        .audit-col-2 {
            left: 175px;
            min-width: 120px;
            max-width: 120px;
        }
        .audit-col-3 {
            left: 295px;
            min-width: 100px;
            max-width: 100px;
        }

        /* ── Shadow divider after the last frozen column ── */
        .audit-col-3::after {
            content: '';
            position: absolute;
            top: 0;
            right: -6px;
            bottom: 0;
            width: 6px;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.08), transparent);
            pointer-events: none;
        }

        /* ── Row hover keeps sticky cells consistent ── */
        .audit-sticky-table tbody tr:hover .audit-sticky-col {
            background: #f8f9fa !important;
        }
    </style>
</div>
