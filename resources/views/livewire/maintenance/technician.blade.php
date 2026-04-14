<div>
    {{-- Filters Card --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fa fa-filter fs-5 me-2 text-primary"></i>
                    <h5 class="mb-0 fw-bold">Complaints Filter</h5>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-refresh me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body py-3">
            <div class="row g-2">
                <div class="col-md-3" wire:ignore>
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-folder-open text-primary me-1"></i>Group/Project</label>
                    {{ html()->select('filter_group_id', [])->value($filterGroup ?? '')->class('select-property_group_id')->id('filter_group_id')->placeholder('All Groups') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-building text-success me-1"></i>Building</label>
                    {{ html()->select('filter_building_id', [])->value($filterBuilding ?? '')->class('select-property_building_id')->id('filter_building_id')->placeholder('All Buildings')->attribute('data-group-select', '#filter_group_id') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-key text-warning me-1"></i>Property No</label>
                    {{ html()->select('filter_property_id', [])->value($filterProperty ?? '')->class('select-property_id')->id('filter_property_id')->placeholder('All Properties')->attribute('data-building-select', '#filter_building_id')->attribute('data-group-select', '#filter_group_id') }}
                </div>
                <div class="col-md-3" wire:ignore>
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-user text-info me-1"></i>Customer</label>
                    {{ html()->select('filter_customer_id', [])->value($filterCustomer ?? '')->class('select-customer_id')->id('filter_customer_id')->placeholder('All Customers') }}
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-exclamation-triangle text-warning me-1"></i>Priority</label>
                    <select wire:model.live="filterPriority" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-tags text-secondary me-1"></i>Segment</label>
                    <select wire:model.live="filterSegment" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="ppmc">PPMC</option>
                        <option value="corrective">Corrective</option>
                        <option value="preparation">Preparation</option>
                    </select>
                </div>
                <div class="col-md-2" wire:ignore>
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-user-cog text-danger me-1"></i>Technician</label>
                    <select id="filter_technician_id" class="form-select form-select-sm">
                        <option value="">All Technicians</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-circle text-success me-1"></i>Status</label>
                    <select wire:model.live="filterStatus" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-folder text-info me-1"></i>Category</label>
                    <select wire:model.live="filterCategory" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2" wire:ignore>
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-exclamation-circle text-warning me-1"></i>Complaint</label>
                    <select id="filter_complaint_id" class="form-select form-select-sm">
                        <option value="">All Complaints</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar-o text-primary me-1"></i>From Date</label>
                    <input type="date" wire:model.live="from_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-calendar text-danger me-1"></i>To Date</label>
                    <input type="date" wire:model.live="to_date" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-search text-muted me-1"></i>Search</label>
                    <input type="text" wire:model.live.debounce.500ms="search" class="form-control form-control-sm" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small mb-1"><i class="fa fa-list text-muted me-1"></i>Per Page</label>
                    <select wire:model.live="limit" class="form-select form-select-sm">
                        <option value="15">15</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Results Table --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fa fa-list fs-5 me-2 text-success"></i>
                    <h5 class="mb-0 fw-bold">Complaints</h5>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill ms-2 px-2" style="font-size: 0.68rem;">
                        {{ $data->total() }}
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th class="fw-semibold ps-3 small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px; cursor: pointer;" wire:click="sortBy('maintenance_complaints.id')">
                                # @if ($sortField === 'maintenance_complaints.id') <i class="fa fa-sort-{{ $sortDirection }}"></i> @endif
                            </th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px; cursor: pointer;" wire:click="sortBy('maintenances.date')">
                                Date @if ($sortField === 'maintenances.date') <i class="fa fa-sort-{{ $sortDirection }}"></i> @endif
                            </th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Building</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Property</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Customer</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Category</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Complaint</th>
                            <th class="fw-semibold small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px;">Technician</th>
                            <th class="fw-semibold text-center small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px; width: 90px;">Priority</th>
                            <th class="fw-semibold text-center small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px; width: 90px;">Status</th>
                            <th class="fw-semibold text-center small text-uppercase text-muted border-0" style="font-size: 0.7rem; letter-spacing: 0.5px; width: 70px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $row)
                            @php
                                $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
                                $priorityColor = $priorityColors[$row->maintenance_priority] ?? 'secondary';
                                $statusEnum = \App\Enums\Maintenance\MaintenanceComplaintStatus::tryFrom($row->status?->value ?? $row->status ?? 'pending');
                                $statusColor = $statusEnum?->color() ?? 'warning';
                                $statusLabel = $statusEnum?->label() ?? 'Pending';
                            @endphp
                            <tr wire:key="tech-row-{{ $row->id }}">
                                <td class="ps-3 text-muted small">{{ $row->id }}</td>
                                <td class="small">
                                    <div>{{ \Carbon\Carbon::parse($row->maintenance_date)->format('d M Y') }}</div>
                                    @if ($row->maintenance_time)
                                        <div class="text-muted" style="font-size: 0.65rem;">{{ $row->maintenance_time }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $row->building_name ?: '-' }}</td>
                                <td class="small fw-semibold">{{ $row->property_number ?: '-' }}</td>
                                <td class="small">{{ $row->customer_name ?: '-' }}</td>
                                <td class="small text-muted">{{ $row->category_name ?: '-' }}</td>
                                <td class="small fw-medium text-dark">{{ $row->complaint_name ?: '-' }}</td>
                                <td class="small">
                                    @if ($row->technician_name)
                                        <span><i class="fa fa-user-circle text-info me-1"></i>{{ $row->technician_name }}</span>
                                    @else
                                        <span class="text-muted fst-italic">Not Assigned</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $priorityColor }} bg-opacity-10 text-{{ $priorityColor }} rounded-pill" style="font-size: 0.65rem;">
                                        {{ ucfirst($row->maintenance_priority ?? '-') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} rounded-pill" style="font-size: 0.65rem;">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @can('maintenance.view')
                                        <a href="{{ route('property::maintenance::complaint', $row->id) }}"
                                            class="btn btn-sm btn-outline-primary rounded-circle p-0 d-inline-flex align-items-center justify-content-center"
                                            style="width: 26px; height: 26px;" title="Open Complaint">
                                            <i class="fa fa-eye" style="font-size: 0.65rem;"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light mb-2" style="width: 50px; height: 50px;">
                                        <i class="fa fa-inbox text-muted" style="font-size: 1.3rem; opacity: 0.4;"></i>
                                    </div>
                                    <div class="text-muted small">No complaints found.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($data->hasPages())
            <div class="card-footer bg-white border-top py-2">
                {{ $data->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // Property cascading filter
                $('#filter_group_id').on('change', function() {
                    @this.set('filterGroup', $(this).val());
                });
                $('#filter_building_id').on('change', function() {
                    @this.set('filterBuilding', $(this).val());
                });
                $('#filter_property_id').on('change', function() {
                    @this.set('filterProperty', $(this).val());
                });
                $('#filter_customer_id').on('change', function() {
                    @this.set('filterCustomer', $(this).val());
                });

                // Technician TomSelect
                if (document.getElementById('filter_technician_id')) {
                    var technicianSelect = new TomSelect('#filter_technician_id', {
                        plugins: [],
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        load: function(query, callback) {
                            fetch("{{ route('users::list') }}?query=" + encodeURIComponent(query))
                                .then(response => response.json())
                                .then(json => callback(json.items))
                                .catch(() => callback());
                        },
                        onFocus: function() { this.load(''); },
                        onChange: function(value) { @this.set('filterTechnician', value); },
                        render: {
                            option: function(item, escape) { return `<div>${escape(item.name || '')}</div>`; },
                            item: function(item, escape) { return `<div>${escape(item.name || '')}</div>`; },
                        },
                    });
                }

                // Complaint TomSelect
                if (document.getElementById('filter_complaint_id')) {
                    var complaintSelect = new TomSelect('#filter_complaint_id', {
                        plugins: [],
                        valueField: 'id',
                        labelField: 'name',
                        searchField: 'name',
                        load: function(query, callback) {
                            var categoryId = @this.get('filterCategory');
                            var url = "{{ route('settings::complaint::list') }}?query=" + encodeURIComponent(query);
                            if (categoryId) url += "&complaint_category_id=" + categoryId;
                            fetch(url)
                                .then(response => response.json())
                                .then(json => callback(json.items))
                                .catch(() => callback());
                        },
                        onFocus: function() { this.load(''); },
                        onChange: function(value) { @this.set('filterComplaint', value); },
                        render: {
                            option: function(item, escape) { return `<div>${escape(item.name || '')}</div>`; },
                            item: function(item, escape) { return `<div>${escape(item.name || '')}</div>`; },
                        },
                    });
                }
            });
        </script>
    @endpush
</div>
