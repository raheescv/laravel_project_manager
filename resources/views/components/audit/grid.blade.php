@props([
    'audits',
    'emptyMessage' => 'No audit entries found.',
    'hideColumns' => ['id', 'tenant_id', 'branch_id', 'created_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'],
    'showRecord' => false,
])

@php
    $auditCollection = collect($audits)->values();

    $valuesOf = fn ($values) => is_array($values) ? $values : (array) ($values ?? []);

    $columns = $auditCollection
        ->flatMap(fn ($audit) => array_merge(array_keys($valuesOf($audit->old_values)), array_keys($valuesOf($audit->new_values))))
        ->unique()
        ->reject(fn ($key) => in_array($key, $hideColumns, true))
        ->values();

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

    $eventBadge = fn ($event) => match ($event) {
        'created' => 'success',
        'updated' => 'primary',
        'deleted' => 'danger',
        'restored' => 'info',
        default => 'secondary',
    };
@endphp

@if ($auditCollection->isEmpty())
    <div class="text-center text-muted small py-3">
        <i class="fa fa-history opacity-25 me-1"></i> {{ $emptyMessage }}
    </div>
@else
    <div class="table-responsive audit-grid">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="bg-light text-muted">
                <tr>
                    <th class="text-nowrap">#</th>
                    @if ($showRecord)
                        <th class="text-nowrap">Record</th>
                    @endif
                    <th class="text-nowrap">Event</th>
                    <th class="text-nowrap">User</th>
                    <th class="text-nowrap">Date &amp; Time</th>
                    @foreach ($columns as $column)
                        <th class="text-nowrap">{{ \Illuminate\Support\Str::headline($column) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($auditCollection as $index => $audit)
                    @php
                        $oldValues = $valuesOf($audit->old_values);
                        $newValues = $valuesOf($audit->new_values);
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $index + 1 }}</td>
                        @if ($showRecord)
                            <td class="text-nowrap">#{{ $audit->auditable_id }}</td>
                        @endif
                        <td>
                            <span class="badge bg-{{ $eventBadge($audit->event) }}-subtle text-{{ $eventBadge($audit->event) }} border border-{{ $eventBadge($audit->event) }}-subtle">
                                {{ ucfirst($audit->event) }}
                            </span>
                        </td>
                        <td class="text-nowrap">{{ $audit->user?->name ?? 'System' }}</td>
                        <td class="text-nowrap text-muted" title="{{ $audit->created_at?->diffForHumans() }}">
                            {{ $audit->created_at?->format('d-m-Y h:i A') }}
                        </td>
                        @foreach ($columns as $column)
                            @php
                                $hasOld = array_key_exists($column, $oldValues);
                                $hasNew = array_key_exists($column, $newValues);
                            @endphp
                            <td class="audit-grid-cell">
                                @if ($hasOld && $hasNew)
                                    <span class="audit-grid-old">{{ $formatValue($oldValues[$column]) }}</span>
                                    <i class="fa fa-long-arrow-right text-muted mx-1"></i>
                                    <span class="audit-grid-new">{{ $formatValue($newValues[$column]) }}</span>
                                @elseif ($hasNew)
                                    <span>{{ $formatValue($newValues[$column]) }}</span>
                                @elseif ($hasOld)
                                    <span class="audit-grid-old">{{ $formatValue($oldValues[$column]) }}</span>
                                @else
                                    <span class="text-muted opacity-50">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @once
        @push('styles')
            <style>
                .audit-grid table { font-size: .74rem; }
                .audit-grid th { font-weight: 600; padding: .35rem .55rem; }
                .audit-grid td { padding: .3rem .55rem; }
                .audit-grid-cell { white-space: nowrap; max-width: 260px; overflow: hidden; text-overflow: ellipsis; }
                .audit-grid-old { color: var(--bs-danger); text-decoration: line-through; opacity: .75; }
                .audit-grid-new { color: var(--bs-success); font-weight: 600; }
            </style>
        @endpush
    @endonce
@endif
