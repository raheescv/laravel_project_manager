<div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-gradient-primary text-white border-0">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-white bg-opacity-25 p-2 me-3">
                    <i class="fa fa-history fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 text-white">Audit Trail</h5>
                    <small class="opacity-75">Track all changes and activities</small>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-white text-primary fs-6 px-3 py-2">
                        {{ $audits->count() }} Records
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-bolt text-warning me-2"></i>
                                    <span class="fw-semibold text-dark">Event</span>
                                </div>
                            </th>
                            <th class="border-0 py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-link text-info me-2"></i>
                                    <span class="fw-semibold text-dark">URL</span>
                                </div>
                            </th>
                            <th class="border-0 py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-arrow-left text-danger me-2"></i>
                                    <span class="fw-semibold text-dark">Previous Values</span>
                                </div>
                            </th>
                            <th class="border-0 py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-arrow-right text-success me-2"></i>
                                    <span class="fw-semibold text-dark">New Values</span>
                                </div>
                            </th>
                            <th class="border-0 py-3 px-4">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user text-primary me-2"></i>
                                    <span class="fw-semibold text-dark">User</span>
                                </div>
                            </th>
                            <th class="border-0 py-3 px-4 text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <i class="fa fa-clock text-secondary me-2"></i>
                                    <span class="fw-semibold text-dark">Timestamp</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($audits as $item)
                            <tr class="border-bottom">
                                <td class="py-3 px-4">
                                    <span
                                        class="badge
                                        @if ($item->event === 'created') bg-success
                                        @elseif($item->event === 'updated') bg-warning text-dark
                                        @elseif($item->event === 'deleted') bg-danger
                                        @else bg-info @endif
                                        px-3 py-2 fs-7">
                                        <i
                                            class="fa
                                            @if ($item->event === 'created') fa-plus
                                            @elseif($item->event === 'updated') fa-edit
                                            @elseif($item->event === 'deleted') fa-trash
                                            @else fa-cog @endif
                                            me-1"></i>
                                        {{ ucfirst($item->event) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="text-truncate" style="max-width: 200px;">
                                            <code class="text-muted small">{{ str_replace(url('/'), '', $item->url ?: 'N/A') }}</code>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    @if ($item->old_values && count($item->old_values) > 0)
                                        <div class="audit-values">
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#oldValues{{ $item->id }}"
                                                aria-expanded="false">
                                                <i class="fa fa-eye me-1"></i>
                                                View ({{ count($item->old_values) }} fields)
                                            </button>
                                            <div class="collapse mt-2" id="oldValues{{ $item->id }}">
                                                <div class="card card-body bg-light border-0 small">
                                                    @foreach ($item->old_values as $key => $value)
                                                        <div class="mb-1">
                                                            <span class="fw-semibold text-primary">{{ $key }}:</span>
                                                            <span class="text-muted">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fa fa-minus-circle me-1"></i>No previous values
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    @if ($item->new_values && count($item->new_values) > 0)
                                        <div class="audit-values">
                                            <button class="btn btn-outline-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#newValues{{ $item->id }}"
                                                aria-expanded="false">
                                                <i class="fa fa-eye me-1"></i>
                                                View ({{ count($item->new_values) }} fields)
                                            </button>
                                            <div class="collapse mt-2" id="newValues{{ $item->id }}">
                                                <div class="card card-body bg-light border-0 small">
                                                    @foreach ($item->new_values as $key => $value)
                                                        <div class="mb-1">
                                                            <span class="fw-semibold text-success">{{ $key }}:</span>
                                                            <span class="text-dark">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="fa fa-minus-circle me-1"></i>No new values
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                            {{ strtoupper(substr($item->user?->name ?? 'System', 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-medium">{{ $item->user?->name ?? 'System' }}</div>
                                            @if ($item->user?->email)
                                                <small class="text-muted">{{ $item->user->email }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-end">
                                    <div class="text-end">
                                        <div class="fw-medium text-dark">
                                            {{ $item->created_at->format('M d, Y') }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->created_at->format('h:i A') }}
                                        </div>
                                        <div class="small text-primary">
                                            <i class="fa fa-clock me-1"></i>
                                            {{ $item->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                            <i class="fa fa-history text-muted" style="font-size: 2rem;"></i>
                                        </div>
                                        <h5 class="text-muted mb-2">No Audit Records Found</h5>
                                        <p class="text-muted mb-0">No changes have been recorded for this item yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($audits->count() > 0)
            <div class="card-footer bg-light border-0">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fa fa-info-circle me-1"></i>
                            Showing audit trail for {{ class_basename($audits->first()->auditable_type ?? '') }} record
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="fa fa-shield-alt me-1"></i>
                            All changes are permanently logged for security
                        </small>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .audit-values .collapse {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .fs-7 {
            font-size: 0.875rem;
        }

        .badge {
            letter-spacing: 0.5px;
        }

        .card {
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
    </style>
</div>
