<div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <h5 class="fw-bold mb-1">
                        <i class="fa fa-history text-primary me-1"></i>Audit History
                    </h5>
                    @if ($audits->first())
                        <p class="text-muted small mb-0">
                            <b>{{ class_basename($audits->first()->auditable_type ?? '') }} #{{ $audits->first()->auditable_id ?? '' }}</b>
                            <span class="text-muted">&middot; {{ $audits->first()->auditable_type ?? '' }}</span>
                        </p>
                    @endif
                </div>
                @if ($audits->count() > 0)
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                        {{ $audits->count() }} {{ \Illuminate\Support\Str::plural('record', $audits->count()) }}
                    </span>
                @endif
            </div>

            @if ($audits->count() > 0)
                <x-audit.table :audits="$audits" />
            @else
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="fa fa-history text-muted" style="font-size: 2rem;"></i>
                    </div>
                    <h5 class="text-muted mb-2">No Audit Records Found</h5>
                    <p class="text-muted mb-0 small">No changes have been recorded for this item yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
