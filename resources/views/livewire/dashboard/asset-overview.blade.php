<div class="row g-3">
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Total Assets</div>
                <div class="fs-3 fw-bold text-dark">{{ number_format($totalAssets) }}</div>
                <div class="small text-muted">All active and historical asset records</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Fully Depreciated</div>
                <div class="fs-3 fw-bold text-success">{{ number_format($fullyDepreciated) }}</div>
                <div class="small text-muted">Assets with no remaining book value</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Due For Depreciation</div>
                <div class="fs-3 fw-bold text-warning">{{ number_format($dueSchedules) }}</div>
                <div class="small text-muted">Pending schedules due as of today</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold">Disposed This Month</div>
                <div class="fs-3 fw-bold text-danger">{{ number_format($disposedThisMonth) }}</div>
                <div class="small text-muted">Assets marked disposed in the current month</div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom py-3">
                <h6 class="mb-0 fw-bold"><i class="fa fa-sitemap me-2 text-primary"></i>Assets By Group</h6>
            </div>
            <div class="card-body">
                @forelse ($assetsByGroup as $group => $count)
                    <div class="d-flex justify-content-between align-items-center py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div class="fw-medium">{{ $group }}</div>
                        <span class="badge bg-primary-subtle text-primary">{{ $count }}</span>
                    </div>
                @empty
                    <div class="text-muted">No asset groups found yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
