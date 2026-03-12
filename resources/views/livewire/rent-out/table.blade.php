<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- ═══ Top Bar: Actions + Show/Search ═══ --}}
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can($config->createPermission)
                        <a href="{{ route($config->createRoute) }}" class="btn btn-primary d-flex align-items-center shadow-sm">
                            <i class="demo-psi-add me-2 fs-5"></i>
                            Add New
                        </a>
                    @endcan
                    @can($config->deletePermission)
                        @if(count($selected) > 0)
                            <button class="btn btn-danger btn-sm d-flex align-items-center shadow-sm" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endif
                    @endcan
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
                                <input type="text" wire:model.live="search" autofocus placeholder="{{ $config->searchPlaceholder }}" class="form-control form-control-sm border-secondary-subtle shadow-sm" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- ═══ Filter Row 1: Group, Building, Property, Customer, Status ═══ --}}
            <div class="row g-3">
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-folder-open text-primary me-1 small"></i> Group/Project
                    </label>
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('rent_out_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('rent_out_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#rent_out_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('rent_out_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#rent_out_filterBuilding')->attribute('data-group-select', '#rent_out_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('rent_out_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-flag text-primary me-1 small"></i> Status
                    </label>
                    {{ html()->select('filterStatus', rentOutStatusOptions())->value($filterStatus)->class('form-select form-select-sm border-secondary-subtle shadow-sm')->attribute('wire:model.live', 'filterStatus')->placeholder('All Status') }}
                </div>
            </div>

            {{-- ═══ Filter Row 2: Dates + Utilities ═══ --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar text-primary me-1 small"></i> From Date
                    </label>
                    <input type="date" wire:model.live="fromDate" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar-check-o text-primary me-1 small"></i> To Date
                    </label>
                    <input type="date" wire:model.live="toDate" class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-bolt text-primary me-1 small"></i> Electricity & Water
                    </label>
                    <select wire:model.live="electricityFilter" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="1">Included</option>
                        <option value="0">Not Included</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-snowflake-o text-primary me-1 small"></i> AC
                    </label>
                    <select wire:model.live="acFilter" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="1">Included</option>
                        <option value="0">Not Included</option>
                    </select>
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-wifi text-primary me-1 small"></i> Wifi
                    </label>
                    <select wire:model.live="wifiFilter" class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="1">Included</option>
                        <option value="0">Not Included</option>
                    </select>
                </div>
            </div>

            {{-- ═══ Column Visibility + Reset ═══ --}}
            <div class="row mt-3">
                <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                    {{-- Column Visibility Dropdown --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="fa fa-columns"></i>
                            <span>Column visibility</span>
                        </button>
                        <ul class="dropdown-menu shadow" style="min-width:220px;">
                            <li class="dropdown-header fw-semibold text-muted" style="font-size:.75rem; letter-spacing:.04em;">TOGGLE COLUMNS</li>
                            <li><hr class="dropdown-divider my-1"></li>
                            @php
                                $columnLabels = [
                                    'id' => 'ID',
                                    'customer' => 'Customer',
                                    'property' => 'Property',
                                    'building' => 'Building',
                                    'start_date' => 'Start Date',
                                    'end_date' => 'End Date',
                                    'rent' => $config->amountLabel,
                                    'status' => 'Status',
                                ];
                            @endphp
                            @foreach($columnLabels as $key => $label)
                                <li>
                                    <label class="dropdown-item d-flex align-items-center gap-2 py-2" style="cursor:pointer; font-size:.85rem;">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" @checked($columns[$key]) wire:click="toggleColumn('{{ $key }}')" style="cursor:pointer;">
                                        </div>
                                        {{ $label }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Reset Filters --}}
                    @if($this->activeFilterCount > 0)
                        <button wire:click="resetFilters" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm">
                            <i class="fa fa-times"></i>
                            Reset Filters
                            <span class="badge bg-danger ms-1">{{ $this->activeFilterCount }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ Table Body ═══ --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllCheckbox" />
                                    <label class="form-check-label" for="selectAllCheckbox">
                                        @if($columns['id'])
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                        @endif
                                    </label>
                                </div>
                            </th>
                            @if($columns['customer'])
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if($columns['property'])
                                <th class="fw-semibold">Property</th>
                            @endif
                            @if($columns['building'])
                                <th class="fw-semibold">Building</th>
                            @endif
                            @if($columns['start_date'])
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="start_date" label="Start Date" /> </th>
                            @endif
                            @if($columns['end_date'])
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="end_date" label="End Date" /> </th>
                            @endif
                            @if($columns['rent'])
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="rent" :label="$config->amountLabel" /> </th>
                            @endif
                            @if($columns['status'])
                                <th class="fw-semibold">Status</th>
                            @endif
                            <th class="fw-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="checkbox{{ $item->id }}" />
                                        <label class="form-check-label" for="checkbox{{ $item->id }}">
                                            @if($columns['id'])
                                                <span class="badge bg-light text-dark border">#{{ $item->id }}</span>
                                            @endif
                                        </label>
                                    </div>
                                </td>
                                @if($columns['customer'])
                                    <td>
                                        @if($item->customer)
                                            <i class="fa fa-user me-1 text-muted opacity-75"></i>{{ $item->customer->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                @if($columns['property'])
                                    <td>
                                        @if($item->property)
                                            <i class="fa fa-home me-1 text-muted opacity-75"></i>{{ $item->property->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                @if($columns['building'])
                                    <td>
                                        @if($item->building)
                                            <i class="fa fa-building-o me-1 text-muted opacity-75"></i>{{ $item->building->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                @if($columns['start_date'])
                                    <td>
                                        @if($item->start_date)
                                            <i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $item->start_date->format('Y-m-d') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                @if($columns['end_date'])
                                    <td>
                                        @if($item->end_date)
                                            <i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $item->end_date->format('Y-m-d') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                                @if($columns['rent'])
                                    <td class="fw-medium">{{ number_format($item->rent, 2) }}</td>
                                @endif
                                @if($columns['status'])
                                    <td>
                                        @if($item->status)
                                            <span class="badge bg-{{ $item->status->color() }}">{{ $item->status->label() }}</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can($config->viewPermission)
                                            <a href="{{ route($config->viewRoute, $item->id) }}" class="btn btn-light btn-sm" title="View" data-bs-toggle="tooltip">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can($config->editPermission)
                                            <a href="{{ route($config->editRoute, $item->id) }}" class="btn btn-info btn-sm text-white" title="Edit {{ $config->singularLabel }}" data-bs-toggle="tooltip">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ collect($columns)->filter()->count() + 2 }}" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    {{ $config->emptyMessage }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data->hasPages())
                <div class="p-3 border-top">
                    {{ $data->links() }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                // ── Helper: clear & reload a TomSelect by ID ──
                function clearAndReload(id) {
                    var el = document.getElementById(id);
                    if (el && el.tomselect) {
                        el.tomselect.clear();
                        el.tomselect.clearOptions();
                        el.tomselect.load('');
                    }
                }

                // ── Cascade: Group → Building → Property ──
                $('#rent_out_filterGroup').on('change', function(e) {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('rent_out_filterBuilding');
                    clearAndReload('rent_out_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#rent_out_filterBuilding').on('change', function(e) {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('rent_out_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#rent_out_filterProperty').on('change', function(e) {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#rent_out_filterCustomer').on('change', function(e) {
                    @this.set('filterCustomer', $(this).val() || '');
                });

                // ── Reset TomSelect on Livewire reset dispatch ──
                window.addEventListener('reset-rent-out-filters', () => {
                    ['rent_out_filterGroup', 'rent_out_filterBuilding', 'rent_out_filterProperty', 'rent_out_filterCustomer'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el && el.tomselect) {
                            el.tomselect.clear();
                        }
                    });
                });

                // ── Table refresh event ──
                window.addEventListener('{{ $config->refreshTableEvent }}', event => {
                    Livewire.dispatch("{{ $config->refreshEvent }}");
                });

                // ── Initialize tooltips ──
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, { boundary: document.body });
                });
            });
        </script>
    @endpush
</div>
