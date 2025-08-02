<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    <div class="btn-group shadow-sm">
                        @can('apilog.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="demo-pli-recycling me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endcan
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="demo-psi-magnifi-glass"></i>
                                </span>
                                <input type="text" id="search" wire:model.live="search" placeholder="Search API logs..." class="form-control border-secondary-subtle shadow-sm" autocomplete="off"
                                    aria-label="Search API logs">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="row g-3">

                <div class="col-lg-3 col-md-6">
                    <div>
                        <label for="from_date" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-calendar me-1 text-muted"></i>
                            From Date
                        </label>
                        <input type="date" wire:model.live="from_date" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div>
                        <label for="to_date" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-calendar me-1 text-muted"></i>
                            To Date
                        </label>
                        <input type="date" wire:model.live="to_date" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div>
                        <label for="status" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-check me-1 text-muted"></i>
                            Status
                        </label>
                        <select wire:model.live="status" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div>
                        <label for="endpoint" class="form-label small fw-medium text-capitalize">
                            <i class="demo-psi-link me-1 text-muted"></i>
                            Endpoint
                        </label>
                        <input type="text" wire:model.live="endpoint" placeholder="Filter by endpoint..." class="form-control form-control-sm border-secondary-subtle shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="endpoint" label="Endpoint" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="method" label="Method" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="request" label="Request" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="response" label="Response" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" />
                            </th>
                            <th class="border-0">
                                <span class="text-muted small fw-semibold">User</span>
                            </th>
                            <th class="border-0">
                                <span class="text-muted small fw-semibold cursor-pointer" wire:click="sortBy('created_at')">
                                    Date
                                    @if ($sortField === 'created_at')
                                        <i class="demo-psi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-2"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="border-0" style="width: 120px;">
                                <span class="text-muted small fw-semibold">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary rounded-pill">{{ $item->id }}</span>
                                    </div>
                                </td>
                                <td>
                                    <code class="bg-light rounded px-2 py-1 small text-break">{{ Str::limit($item->endpoint, 50) }}</code>
                                </td>
                                <td> <span class="badge bg-info text-white">{{ $item->method }}</span> </td>
                                <td> <span class="badge bg-info text-white">{{ $item->request }}</span> </td>
                                <td> <span class="badge bg-info text-white">{{ $item->response }}</span> </td>
                                <td>
                                    @if ($item->status === 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif($item->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $item->user_name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ systemDateTime($item->created_at) }}</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            wire:click="$dispatch('showApiLogDetail', { apiLogId: {{ $item->id }} })">
                                            <i class="demo-psi-eye"></i>
                                        </button>
                                        @if ($item->status === 'failed')
                                            <button type="button" class="btn btn-outline-warning btn-sm" wire:click="retryApiCall({{ $item->id }})"
                                                wire:confirm="Are you sure you want to retry this API call?">
                                                <i class="demo-psi-refresh"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="demo-psi-information display-4"></i>
                                        <p class="mt-2 mb-0">No API logs found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($data->hasPages())
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} results
                    </div>
                    <div>
                        {{ $data->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
