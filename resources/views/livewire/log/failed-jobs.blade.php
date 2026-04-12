<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row g-3 align-items-end">
                <div class="col-lg-7">
                    <label class="form-label small fw-semibold text-muted">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-secondary-subtle">
                            <i class="demo-psi-magnifi-glass"></i>
                        </span>
                        <input type="text" wire:model.live="search" class="form-control border-secondary-subtle shadow-sm"
                            placeholder="Search by job, queue, UUID, exception, or ID">
                    </div>
                </div>
                <div class="col-lg-3">
                    <label class="form-label small fw-semibold text-muted">Queue</label>
                    <select wire:model.live="queue" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Queues</option>
                        @foreach ($queues as $queueName)
                            <option value="{{ $queueName }}">{{ $queueName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small fw-semibold text-muted">Rows</label>
                    <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th class="ps-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                            </th>
                            <th>
                                <span class="text-muted small fw-semibold">Job</span>
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="queue" label="Queue" />
                            </th>
                            <th>
                                <span class="text-muted small fw-semibold">Status</span>
                            </th>
                            <th>
                                <span class="text-muted small fw-semibold">Failure</span>
                            </th>
                            <th>
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="failed_at" label="Failed At" />
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
                                    <div class="fw-semibold text-dark">{{ $this->resolveJobName($item->payload) }}</div>
                                    <div class="small text-muted text-break">{{ $item->uuid }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->queue }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-danger">Failed</span>
                                </td>
                                <td>
                                    <div class="small text-danger-emphasis fw-medium">{{ $this->failureMessage($item->exception) }}</div>
                                    <div class="small text-muted text-break">{{ \Illuminate\Support\Str::limit($item->exception, 140) }}</div>
                                </td>
                                <td>
                                    <span class="small text-muted">{{ systemDateTime($item->failed_at) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="demo-psi-information display-6"></i>
                                        <p class="mt-2 mb-0">No failed jobs found.</p>
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
                        Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} failed jobs
                    </div>
                    <div>{{ $data->links() }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
