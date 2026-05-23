@props([
    'audits',
    'emptyMessage' => 'No audit entries found.',
    'hideColumns' => [],
])

@php
    $auditCollection = $audits instanceof \Illuminate\Support\Collection ? $audits : collect($audits);
    $auditCollection = $auditCollection->values();

    $allKeys = collect();
    foreach ($auditCollection as $audit) {
        $oldValues = is_array($audit->old_values ?? null) ? $audit->old_values : (array) ($audit->old_values ?? []);
        $newValues = is_array($audit->new_values ?? null) ? $audit->new_values : (array) ($audit->new_values ?? []);
        $allKeys = $allKeys->merge(array_keys($oldValues))->merge(array_keys($newValues));
    }
    $allKeys = $allKeys->unique()->reject(fn ($k) => in_array($k, $hideColumns, true))->values();

    $formatValue = function ($value) {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            return json_encode($value);
        }
        if ($value === null || $value === '') {
            return '-';
        }
        return (string) $value;
    };

    $eventBadge = function ($event) {
        return match ($event) {
            'created' => 'bg-success',
            'updated' => 'bg-primary',
            'deleted' => 'bg-danger',
            'restored' => 'bg-info',
            default => 'bg-secondary',
        };
    };
@endphp

<div class="audit-pivot-list">
    @if ($auditCollection->isEmpty())
        <div class="text-center text-muted py-4">
            <i class="fa fa-history fa-3x opacity-25 d-block mb-2"></i>
            <small>{{ $emptyMessage }}</small>
        </div>
    @else
        <div class="audit-pivot-scroll">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="audit-pivot-field-col audit-pivot-sticky">Field</th>
                        @foreach ($auditCollection as $index => $audit)
                            @php
                                $userName = $audit->user?->name ?? 'System';
                                $userInitial = strtoupper(mb_substr($userName, 0, 1));
                                $createdAt = $audit->created_at instanceof \Carbon\CarbonInterface
                                    ? $audit->created_at->format('Y-m-d H:i:s')
                                    : (string) $audit->created_at;
                            @endphp
                            <th class="audit-pivot-change-col">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="audit-entry-index badge bg-light text-dark border">#{{ $index + 1 }}</span>
                                        <span class="badge {{ $eventBadge($audit->event) }} text-uppercase">{{ $audit->event }}</span>
                                    </div>
                                    <span class="text-nowrap small fw-normal"><i class="fa fa-clock-o me-1 text-primary"></i>{{ $createdAt }}</span>
                                    <span class="text-nowrap small fw-normal">
                                        <span class="audit-user-avatar bg-primary-subtle text-primary me-1">{{ $userInitial }}</span>
                                        <span class="fw-semibold">{{ $userName }}</span>
                                    </span>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($allKeys as $key)
                        <tr>
                            <th class="audit-pivot-field-col audit-pivot-sticky text-muted">
                                {{ \Illuminate\Support\Str::title(str_replace(['_', '-'], ' ', $key)) }}
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
                                @endphp
                                <td class="audit-pivot-change-col">
                                    @if ($changed)
                                        <span class="text-danger text-decoration-line-through">{{ $formatValue($oldVal) }}</span>
                                        <i class="fa fa-arrow-right text-muted mx-1"></i>
                                        <span class="text-success fw-semibold">{{ $formatValue($newVal) }}</span>
                                    @elseif ($hasNew && $audit->event !== 'deleted')
                                        <span class="text-success">{{ $formatValue($newVal) }}</span>
                                    @elseif ($hasOld)
                                        <span class="text-danger">{{ $formatValue($oldVal) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
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
    @endif
</div>

@once
    @push('styles')
        <style>
            .audit-pivot-list .audit-pivot-scroll {
                overflow-x: auto;
                overflow-y: visible;
                position: relative;
                max-width: 100%;
            }
            .audit-pivot-list .audit-pivot-table {
                font-size: 0.82rem;
                border-collapse: separate;
                border-spacing: 0;
                margin-bottom: 0;
                width: auto;
                min-width: 100%;
            }
            .audit-pivot-list .audit-pivot-table thead th {
                background: #f4f6fa;
                vertical-align: top;
                font-weight: 600;
                color: #2d3a4b;
                padding: 0.5rem 0.6rem;
                border-bottom: 2px solid #dee2e6;
                white-space: nowrap;
            }
            .audit-pivot-list .audit-pivot-table tbody th {
                background: #fafbfc;
                font-weight: 600;
                color: #6c757d;
                padding: 0.4rem 0.6rem;
                white-space: nowrap;
                text-transform: capitalize;
            }
            .audit-pivot-list .audit-pivot-table tbody td {
                padding: 0.4rem 0.6rem;
                vertical-align: middle;
                word-break: break-word;
            }
            .audit-pivot-list .audit-pivot-sticky {
                position: sticky;
                left: 0;
                z-index: 2;
                box-shadow: 1px 0 0 #dee2e6;
            }
            .audit-pivot-list thead .audit-pivot-sticky {
                z-index: 4;
                background: #f4f6fa !important;
            }
            .audit-pivot-list tbody .audit-pivot-sticky {
                background: #fafbfc !important;
            }
            .audit-pivot-list .audit-pivot-field-col {
                min-width: 160px;
                max-width: 220px;
            }
            .audit-pivot-list .audit-pivot-change-col {
                min-width: 180px;
            }
            .audit-pivot-list .audit-user-avatar {
                width: 20px;
                height: 20px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                font-weight: 600;
                font-size: 0.65rem;
            }
            .audit-pivot-list .audit-entry-index {
                font-size: 0.7rem;
                padding: 0.2em 0.5em;
            }
            @media (max-width: 575.98px) {
                .audit-pivot-list .audit-pivot-table {
                    font-size: 0.76rem;
                }
                .audit-pivot-list .audit-pivot-field-col {
                    min-width: 130px;
                    max-width: 160px;
                }
                .audit-pivot-list .audit-pivot-change-col {
                    min-width: 150px;
                }
            }
        </style>
    @endpush
@endonce
