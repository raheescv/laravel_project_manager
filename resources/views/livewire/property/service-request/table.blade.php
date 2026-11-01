<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('property service request.create')
                        <a href="{{ route('property::service_request::create') }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                            <i class="fa fa-plus-circle me-2"></i>
                            New Request
                        </a>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('property service request.delete')
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
                                <input type="text" wire:model.live="search" autofocus placeholder="Search unit, building, remarks..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
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
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterType" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Types</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterUtility" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Utilities</option>
                        @foreach ($utilities as $utilityId => $utilityName)
                            <option value="{{ $utilityId }}">{{ $utilityName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model.live="filterServiceStatus" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">Rent Inclusion</option>
                        <option value="Included">Included</option>
                        <option value="Excluded">Excluded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" wire:model.live="from_date" class="form-control form-control-sm border-secondary-subtle shadow-sm" placeholder="From Date">
                </div>
                <div class="col-md-2">
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
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="property_service_requests.id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" /> </th>
                            <th class="fw-semibold text-center">Type</th>
                            <th class="fw-semibold">Utility</th>
                            <th class="fw-semibold">Group</th>
                            <th class="fw-semibold">Building</th>
                            <th class="fw-semibold">Unit</th>
                            <th class="fw-semibold text-center">Rent Inclusion</th>
                            <th class="fw-semibold text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="amount" label="Amount" /> </th>
                            <th class="fw-semibold">Requested By</th>
                            <th class="fw-semibold">Approved By</th>
                            <th class="fw-semibold">Financial Approved By</th>
                            <th class="fw-semibold">Completed By</th>
                            <th class="fw-semibold text-center"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="status" label="Status" /> </th>
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
                                <td><span class="fw-medium text-dark small">{{ $item->date?->format('d M Y') }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $item->type->color() }}">{{ $item->type->label() }}</span>
                                </td>
                                <td>{{ $item->utility?->name ?? '-' }}</td>
                                <td>{{ $item->group?->name ?? '-' }}</td>
                                <td>{{ $item->building?->name ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('property::service_request::edit', $item->id) }}" class="fw-medium">{{ $item->property?->number ?? '-' }}</a>
                                </td>
                                <td class="text-center">
                                    @if ($item->rent_out_service_status)
                                        <span class="badge bg-light text-dark border">{{ $item->rent_out_service_status }}</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ currency($item->amount) }}</td>
                                <td><small class="text-muted">{{ $item->requestedBy?->name ?? '-' }}</small></td>
                                <td><small class="text-muted">{{ $item->approvedBy?->name ?? '-' }}</small></td>
                                <td><small class="text-muted">{{ $item->financialApprovedBy?->name ?? '-' }}</small></td>
                                <td><small class="text-muted">{{ $item->completedBy?->name ?? '-' }}</small></td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $item->status->color() }}">{{ $item->status->label() }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('property::service_request::edit', $item->id) }}" class="btn btn-light btn-sm" title="Open" data-bs-toggle="tooltip">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-5 text-muted">
                                    <i class="fa fa-plug fa-3x mb-3 d-block opacity-25"></i>
                                    No service requests found matching your criteria.
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

        @can('property service request.create')
            <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none">
                <a href="{{ route('property::service_request::create') }}" class="btn btn-primary rounded-circle shadow btn-lg">
                    <i class="fa fa-plus"></i>
                </a>
            </div>
        @endcan

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
