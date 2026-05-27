<div>
    {{-- Header --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center">
                <span class="rounded-circle bg-info bg-opacity-10 text-info d-inline-flex align-items-center justify-content-center me-3"
                      style="width:42px;height:42px;">
                    <i class="fa fa-magic fs-5"></i>
                </span>
                <div>
                    <h5 class="mb-0 fw-semibold">Trading Strategies</h5>
                    <small class="text-muted">Enable, toggle paper mode, set capital weight</small>
                </div>
            </div>
            <button wire:click="bootstrap" class="btn btn-sm btn-primary">
                <i class="fa fa-plus-circle me-1"></i> Sync registered strategies
            </button>
        </div>
    </div>

    {{-- Configured strategies --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="fa fa-list-ul text-primary me-2"></i> Configured strategies
            </h6>
            <span class="badge bg-light text-dark">{{ count($rows) }} total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Mode</th>
                        <th class="text-center">Capital weight</th>
                        <th>Last updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="font-monospace fw-semibold">{{ $row['code'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-center">
                                <button wire:click="toggle({{ $row['id'] }})"
                                        class="btn btn-sm {{ $row['is_active'] ? 'btn-success' : 'btn-outline-secondary' }}">
                                    <i class="fa fa-{{ $row['is_active'] ? 'check-circle' : 'circle-o' }} me-1"></i>
                                    {{ $row['is_active'] ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="text-center">
                                <button wire:click="togglePaper({{ $row['id'] }})"
                                        class="btn btn-sm {{ $row['paper_mode'] ? 'btn-info' : 'btn-warning' }}">
                                    <i class="fa fa-{{ $row['paper_mode'] ? 'file-text-o' : 'rocket' }} me-1"></i>
                                    {{ $row['paper_mode'] ? 'Paper' : 'LIVE' }}
                                </button>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">{{ $row['capital_weight'] }}%</span>
                            </td>
                            <td class="text-muted small">{{ $row['updated_at'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fa fa-cubes fs-2 d-block mb-2 opacity-50"></i>
                                No strategies stored yet.
                                <div class="mt-2">
                                    <button wire:click="bootstrap" class="btn btn-sm btn-primary">
                                        <i class="fa fa-plus-circle me-1"></i> Click to sync registered strategies
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Registered in code --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="card-title mb-0">
                <i class="fa fa-cube text-info me-2"></i> Registered in code
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @foreach ($registered as $r)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong>{{ $r['name'] }}</strong>
                                <code class="small text-muted">{{ $r['code'] }}</code>
                            </div>
                            <div class="small text-muted">
                                <strong>Default params:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    @foreach ($r['defaults'] as $k => $v)
                                        <li><code>{{ $k }}</code>: {{ is_scalar($v) ? $v : json_encode($v) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
