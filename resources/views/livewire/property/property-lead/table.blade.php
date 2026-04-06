<div>
    {{-- Filters Card --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-semibold text-dark">
                <i class="fa fa-filter text-primary me-2"></i>Filter Options
            </h5>
            <button type="button" wire:click="clearFilters" class="btn btn-sm btn-light shadow-sm">
                <i class="fa fa-times me-1"></i> Clear filters
            </button>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">From Date</label>
                    <input type="date" wire:model.live="fromDate" class="form-control form-control-sm shadow-sm">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">To Date</label>
                    <input type="date" wire:model.live="toDate" class="form-control form-control-sm shadow-sm">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">Status</label>
                    <select wire:model.live="filterStatus" class="form-select form-select-sm shadow-sm">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">Type</label>
                    <select wire:model.live="filterType" class="form-select form-select-sm shadow-sm">
                        <option value="">All Types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">Source</label>
                    <select wire:model.live="filterSource" class="form-select form-select-sm shadow-sm">
                        <option value="">All Sources</option>
                        @foreach($sources as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">Project / Group</label>
                    <select wire:model.live="filterPropertyGroupId" class="form-select form-select-sm shadow-sm">
                        <option value="">All Projects</option>
                        @foreach($groups as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">Assigned To</label>
                    <select wire:model.live="filterAssignedTo" class="form-select form-select-sm shadow-sm">
                        <option value="">All Salesman</option>
                        @foreach($users as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small fw-semibold text-muted text-uppercase mb-1">Location</label>
                    <select wire:model.live="filterLocation" class="form-select form-select-sm shadow-sm">
                        <option value="">All Locations</option>
                        @foreach($locations as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Summary --}}
    @if($groups && $statuses)
        <div class="card shadow-sm border-0 mb-3 lead-status-summary">
            <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h5 class="mb-0 fw-semibold text-dark">
                    <i class="fa fa-bar-chart text-primary me-2"></i>Lead Status Summary
                </h5>
                <span class="small text-muted d-none d-md-inline">
                    <i class="fa fa-info-circle me-1"></i>Scroll horizontally to view all statuses
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 lead-status-summary-table">
                        <thead>
                            <tr>
                                <th class="ps-3 fw-semibold text-uppercase small text-muted sticky-col">Project / Group</th>
                                @foreach($statuses as $key => $label)
                                    <th class="text-center fw-semibold">
                                        <div class="status-head {{ leadStatusBadgeClass($key) }}" title="{{ $label }}">
                                            {{ $label }}
                                        </div>
                                    </th>
                                @endforeach
                                <th class="text-center fw-semibold text-uppercase small text-muted pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groups as $groupId => $groupName)
                                @php
                                    $row = $statusSummary[$groupId] ?? collect();
                                    $rowTotal = $row->sum('total');
                                @endphp
                                <tr>
                                    <th class="ps-3 sticky-col">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="group-dot"></span>
                                            <span class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="{{ $groupName }}">{{ $groupName }}</span>
                                        </div>
                                    </th>
                                    @foreach($statuses as $key => $label)
                                        @php $count = optional($row->firstWhere('status', $key))->total ?? 0; @endphp
                                        <td class="text-center">
                                            @if($count > 0)
                                                <a href="#" wire:click.prevent="$set('filterStatus','{{ $key }}'); $set('filterPropertyGroupId','{{ $groupId }}')"
                                                    class="text-decoration-none">
                                                    <span class="badge status-count {{ leadStatusBadgeClass($key) }}">{{ $count }}</span>
                                                </a>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center pe-3">
                                        <span class="badge bg-dark-subtle text-dark fw-bold">{{ $rowTotal }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Table Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center">
                    @can('property lead.create')
                        <a href="{{ route('property::lead::create') }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                            <i class="fa fa-plus-circle me-2"></i> New Lead
                        </a>
                    @endcan
                    @can('property lead.delete')
                        <button class="btn btn-danger btn-sm d-flex align-items-center" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected leads?">
                            <i class="fa fa-trash me-md-1 fs-5"></i>
                            <span class="d-none d-md-inline">Delete</span>
                        </button>
                    @endcan
                    @can('property lead.download')
                        <button type="button" class="btn btn-success btn-sm d-flex align-items-center shadow-sm"
                            wire:click="export" wire:loading.attr="disabled" wire:target="export">
                            <span wire:loading.remove wire:target="export">
                                <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </span>
                            <span wire:loading wire:target="export">
                                <i class="fa fa-spinner fa-spin me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Exporting...</span>
                            </span>
                        </button>
                    @endcan
                    <a href="{{ route('property::lead::calendar') }}" class="btn btn-light btn-sm shadow-sm">
                        <i class="fa fa-calendar me-1"></i> Calendar
                    </a>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                                <option value="15">15</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                            </select>
                        </div>
                        <div class="col">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-secondary-subtle">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search by name, mobile, email, company..." class="form-control form-control-sm border-secondary-subtle shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2 ps-3">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllCheckbox">
                                    <label class="form-check-label" for="selectAllCheckbox">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" /></th>
                            <th class="fw-semibold">Mobile</th>
                            <th class="fw-semibold">Email</th>
                            <th class="fw-semibold">Project / Group</th>
                            <th class="fw-semibold">Source</th>
                            <th class="fw-semibold text-center">Type</th>
                            <th class="fw-semibold text-center">Status</th>
                            <th class="fw-semibold">Assigned To</th>
                            <th class="fw-semibold"><x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="updated_at" label="Updated" /></th>
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($list as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="ck{{ $item->id }}">
                                        <label class="form-check-label" for="ck{{ $item->id }}">{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('property::lead::edit', $item->id) }}" class="text-decoration-none fw-semibold text-dark">
                                        <i class="fa fa-user-circle text-primary opacity-75 me-1"></i>{{ $item->name }}
                                    </a>
                                    @if($item->company_name)
                                        <div class="small text-muted"><i class="fa fa-building me-1"></i>{{ $item->company_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($item->mobile)
                                        <i class="fa fa-phone text-success me-1 small"></i>{{ $item->mobile }}
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->email)
                                        <i class="fa fa-envelope text-info me-1 small"></i>{{ $item->email }}
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->group->name ?? '-' }}</span>
                                </td>
                                <td><span class="small">{{ $item->source ?? '-' }}</span></td>
                                <td class="text-center">
                                    @if($item->type === 'Sales')
                                        <span class="badge bg-primary-subtle text-primary">{{ $item->type }}</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">{{ $item->type }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ leadStatusBadgeClass($item->status) }}">{{ $item->status ?? 'New Lead' }}</span>
                                </td>
                                <td>
                                    @if($item->assignee)
                                        <i class="fa fa-user text-muted me-1 small"></i>{{ $item->assignee->name }}
                                    @else
                                        <span class="text-muted small">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $item->updated_at?->diffForHumans() }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can('property lead.view')
                                            <a href="{{ route('property::lead::edit', $item->id) }}" class="btn btn-light btn-sm" data-bs-toggle="tooltip" title="Open lead">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa fa-users fa-3x mb-3 d-block opacity-25"></i>
                                    No leads found matching your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                {{ $list->links() }}
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            /* === Lead Status Summary === */
            .lead-status-summary-table {
                font-size: .78rem;
                border-collapse: separate;
                border-spacing: 0;
            }
            .lead-status-summary-table thead th {
                background: #f8f9fb;
                border-bottom: 1px solid #e9ecef;
                border-top: 1px solid #e9ecef;
                padding: .65rem .5rem;
                white-space: nowrap;
                vertical-align: middle;
            }
            .lead-status-summary-table tbody th,
            .lead-status-summary-table tbody td {
                padding: .6rem .5rem;
                border-bottom: 1px solid #f1f3f5;
                vertical-align: middle;
            }
            .lead-status-summary-table tbody tr:last-child th,
            .lead-status-summary-table tbody tr:last-child td {
                border-bottom: 0;
            }
            .lead-status-summary-table tbody tr:hover {
                background: #fafbff;
            }
            /* Sticky first column on small screens so row label stays visible */
            .lead-status-summary-table .sticky-col {
                position: sticky;
                left: 0;
                background: #fff;
                z-index: 2;
                min-width: 200px;
                box-shadow: 2px 0 4px -2px rgba(0,0,0,.04);
            }
            .lead-status-summary-table thead .sticky-col {
                background: #f8f9fb;
                z-index: 3;
            }
            /* Status pill header */
            .lead-status-summary-table .status-head {
                display: inline-block;
                padding: .3rem .6rem;
                border-radius: 999px;
                font-size: .68rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .3px;
                white-space: nowrap;
            }
            /* Clickable count badge */
            .lead-status-summary-table .status-count {
                min-width: 34px;
                padding: .35rem .55rem;
                font-weight: 700;
                font-size: .72rem;
                border-radius: 6px;
                transition: transform .15s ease;
                display: inline-block;
            }
            .lead-status-summary-table .status-count:hover {
                transform: scale(1.08);
            }
            .lead-status-summary-table .group-dot {
                width: 8px; height: 8px; border-radius: 50%;
                background: var(--bs-primary);
                display: inline-block;
                flex-shrink: 0;
            }
            @media (max-width: 768px) {
                .lead-status-summary-table .sticky-col { min-width: 150px; }
                .lead-status-summary-table .status-head { font-size: .62rem; padding: .25rem .5rem; }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            $(document).ready(function() {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el, { boundary: document.body }); });
            });
        </script>
    @endpush
</div>
