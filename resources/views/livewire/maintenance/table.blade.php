<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('maintenance.create')
                        <a href="{{ route('property::maintenance::create') }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                            <i class="fa fa-plus-circle me-2"></i>
                            New Request
                        </a>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('maintenance.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
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
                                <option value="100">100</option>
                                <option value="500">500</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live="search" autofocus placeholder="Search maintenance..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters Row --}}
            <div class="row mt-3 g-2">
                <div class="col-md-2">
                    <select wire:model.live="filterStatus" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Status</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterPriority" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Priority</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterSegment" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Segments</option>
                        @foreach($segments as $segment)
                            <option value="{{ $segment->value }}">{{ $segment->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" wire:model.live="from_date" class="form-control form-control-sm border-secondary-subtle shadow-sm" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" wire:model.live="to_date" class="form-control form-control-sm border-secondary-subtle shadow-sm" placeholder="To Date">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllCheckbox" />
                                    <label class="form-check-label" for="selectAllCheckbox">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="maintenances.id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" /> </th>
                            <th class="fw-semibold">Property</th>
                            <th class="fw-semibold">Building</th>
                            <th class="fw-semibold">Customer</th>
                            <th class="fw-semibold text-center">Priority</th>
                            <th class="fw-semibold text-center">Segment</th>
                            <th class="fw-semibold text-center">Complaints</th>
                            <th class="fw-semibold text-center"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /> </th>
                            <th class="fw-semibold">Created By</th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="checkbox{{ $item->id }}" />
                                        <label class="form-check-label" for="checkbox{{ $item->id }}">{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark small">
                                        {{ $item->date?->format('d M Y') }}
                                        @if($item->time)
                                            <br><small class="text-muted">{{ $item->time }}</small>
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-medium text-dark">{{ $item->property?->name ?? '-' }}</span>
                                </td>
                                <td>{{ $item->building?->name ?? '-' }}</td>
                                <td>{{ $item->customer?->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if($item->priority)
                                        <span class="badge bg-{{ $item->priority->color() }}">{{ $item->priority->label() }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->segment)
                                        <span class="badge bg-{{ $item->segment->color() }}">{{ $item->segment->label() }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ $item->maintenance_complaints_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($item->status)
                                        <span class="badge bg-{{ $item->status->color() }}">{{ $item->status->label() }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $item->creator?->name ?? '-' }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can('maintenance.edit')
                                            <a href="{{ route('property::maintenance::edit', $item->id) }}" class="btn btn-light btn-sm" title="Edit" data-bs-toggle="tooltip">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endcan
                                        @can('maintenance.assign')
                                            <a href="{{ route('property::maintenance::assign', $item->id) }}" class="btn btn-light btn-sm" title="Assign Technician" data-bs-toggle="tooltip">
                                                <i class="fa fa-user-plus"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa fa-wrench fa-3x mb-3 d-block opacity-25"></i>
                                    No maintenance requests found matching your criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                {{ $data->links() }}
            </div>
        </div>

        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none">
            <a href="{{ route('property::maintenance::create') }}" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </a>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl, { boundary: document.body });
                    });
                });
            </script>
        @endpush
    </div>
</div>
