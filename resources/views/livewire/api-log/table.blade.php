<div>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4 d-flex flex-wrap gap-2">
                    @can('apilog.delete')
                        <button class="btn btn-danger btn-sm d-flex align-items-center gap-1" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="fa fa-trash"></i>
                            <span class="d-none d-md-inline">Delete</span>
                        </button>
                    @endcan
                </div>
                <div class="col-md-8">
                    <div class="row g-2 align-items-center justify-content-end">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm" style="width: 80px;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
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
                    <label for="from_date" class="form-label small fw-medium text-muted mb-1">
                        <i class="fa fa-calendar me-1"></i> From Date
                    </label>
                    <input type="date" wire:model.live="from_date" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="to_date" class="form-label small fw-medium text-muted mb-1">
                        <i class="fa fa-calendar me-1"></i> To Date
                    </label>
                    <input type="date" wire:model.live="to_date" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="status" class="form-label small fw-medium text-muted mb-1">
                        <i class="fa fa-tag me-1"></i> Status
                    </label>
                    <select wire:model.live="status" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="endpoint" class="form-label small fw-medium text-muted mb-1">
                        <i class="fa fa-link me-1"></i> Endpoint
                    </label>
                    <input type="text" wire:model.live="endpoint" placeholder="Filter by endpoint..." class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 ps-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="endpoint" label="Endpoint" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="method" label="Method" />
                            </th>
                            <th class="border-0">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="service_name" label="Service" />
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
                                        <i class="fa fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </span>
                            </th>
                            <th class="border-0 text-center" style="width: 110px;">
                                <span class="text-muted small fw-semibold">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td class="ps-3">
                                    <span class="badge bg-secondary rounded-pill">{{ $item->id }}</span>
                                </td>
                                <td>
                                    <code class="bg-light rounded px-2 py-1 small text-break">{{ Str::limit($item->endpoint, 50) }}</code>
                                </td>
                                <td>
                                    @php
                                        $methodColors = [
                                            'GET' => 'bg-success',
                                            'POST' => 'bg-primary',
                                            'PUT' => 'bg-warning text-dark',
                                            'PATCH' => 'bg-info text-dark',
                                            'DELETE' => 'bg-danger',
                                        ];
                                        $methodUpper = strtoupper($item->method ?? '');
                                        $methodClass = $methodColors[$methodUpper] ?? 'bg-secondary';
                                    @endphp
                                    <span class="badge {{ $methodClass }}">{{ $methodUpper ?: '-' }}</span>
                                </td>
                                <td>
                                    @if ($item->service_name)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $item->service_name }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->response)
                                        <span class="badge bg-light text-dark border">{{ Str::limit($item->response, 20) }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($item->status === 'success')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fa fa-check me-1"></i>Success
                                        </span>
                                    @elseif($item->status === 'failed')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="fa fa-times me-1"></i>Failed
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="fa fa-clock-o me-1"></i>Pending
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-secondary small">{{ $item->user_name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted small">{{ systemDateTime($item->created_at) }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" title="View Details"
                                            wire:click="$dispatch('showApiLogDetail', { apiLogId: {{ $item->id }} })">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                        @if ($item->status === 'failed' && $item->service_name === 'Moq Solutions Sync DayClose Amount')
                                            <button type="button" class="btn btn-outline-warning" title="Retry" wire:click="retryApiCall({{ $item->id }})"
                                                wire:confirm="Are you sure you want to retry this API call?">
                                                <i class="fa fa-refresh"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fa fa-inbox display-4 d-block mb-2 opacity-50"></i>
                                        <p class="mb-0">No API logs found</p>
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
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
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
