<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            {{-- ═══ Top Bar: Actions + Show/Search/Column Visibility ═══ --}}
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can($config->bookingCreatePermission)
                        <a href="{{ route($config->bookingCreateRoute) }}"
                            class="btn btn-primary d-flex align-items-center shadow-sm">
                            <i class="demo-psi-add me-2 fs-5"></i>
                            Add New Booking
                        </a>
                    @endcan
                    @can($config->bookingDeletePermission)
                        @if (count($selected) > 0)
                            <button class="btn btn-danger btn-sm d-flex align-items-center shadow-sm" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endif
                    @endcan
                    <button wire:click="download" class="btn btn-success btn-sm d-flex align-items-center shadow-sm">
                        <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                        <span class="d-none d-md-inline">Excel</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0 text-muted small fw-semibold">Show:</label>
                        </div>
                        <div class="col-auto">
                            <select wire:model.live="limit"
                                class="form-select form-select-sm border-secondary-subtle shadow-sm">
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
                                <input type="text" wire:model.live="search" autofocus
                                    placeholder="{{ $config->bookingSearchPlaceholder }}"
                                    class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                        <div class="col-auto">
                            {{-- Column Visibility Dropdown --}}
                            <div class="dropdown">
                                <button
                                    class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 shadow-sm"
                                    type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="fa fa-columns"></i>
                                    <span class="d-none d-md-inline">Column visibility</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:220px;">
                                    <li class="dropdown-header fw-semibold text-muted"
                                        style="font-size:.75rem; letter-spacing:.04em;">TOGGLE COLUMNS</li>
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    @php
                                        $columnLabels = [
                                            'id' => 'ID',
                                            'customer' => 'Customer',
                                            'group' => 'Group',
                                            'building' => 'Building',
                                            'property' => 'Property No/Unit',
                                            'start_date' => 'Start Date',
                                            'end_date' => 'End Date',
                                            'rent' => $config->amountLabel,
                                            'booking_status' => 'Booking Status',
                                            'status' => 'Status',
                                            'created_at' => 'Created At',
                                        ];
                                    @endphp
                                    @foreach ($columnLabels as $key => $label)
                                        <li>
                                            <label class="dropdown-item d-flex align-items-center gap-2 py-2"
                                                style="cursor:pointer; font-size:.85rem;">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        @checked($columns[$key] ?? false)
                                                        wire:click="toggleColumn('{{ $key }}')"
                                                        style="cursor:pointer;">
                                                </div>
                                                {{ $label }}
                                            </label>
                                        </li>
                                    @endforeach
                                    <li>
                                        <hr class="dropdown-divider my-1">
                                    </li>
                                    <li>
                                        <button class="dropdown-item text-center text-warning fw-semibold"
                                            wire:click="resetColumns" style="font-size:.85rem;">
                                            <i class="fa fa-undo me-1"></i> Reset to Defaults
                                        </button>
                                    </li>
                                </ul>
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
                    {{ html()->select('filterGroup', [])->value('')->class('select-property_group_id-list border-secondary-subtle shadow-sm')->id('booking_filterGroup')->placeholder('All Groups')->attribute('wire:model', 'filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-building text-primary me-1 small"></i> Building
                    </label>
                    {{ html()->select('filterBuilding', [])->value('')->class('select-property_building_id-list border-secondary-subtle shadow-sm')->id('booking_filterBuilding')->placeholder('All Buildings')->attribute('wire:model', 'filterBuilding')->attribute('data-group-select', '#booking_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-home text-primary me-1 small"></i> Property/Unit
                    </label>
                    {{ html()->select('filterProperty', [])->value('')->class('select-property_id-list border-secondary-subtle shadow-sm')->id('booking_filterProperty')->placeholder('All Properties')->attribute('wire:model', 'filterProperty')->attribute('data-building-select', '#booking_filterBuilding')->attribute('data-group-select', '#booking_filterGroup') }}
                </div>
                <div class="col-md-4 col-lg" wire:ignore>
                    <label class="form-label fw-medium">
                        <i class="fa fa-user text-primary me-1 small"></i> Customer
                    </label>
                    {{ html()->select('filterCustomer', [])->value('')->class('select-customer_id-list border-secondary-subtle shadow-sm')->id('booking_filterCustomer')->placeholder('All Customers')->attribute('wire:model', 'filterCustomer') }}
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-flag text-primary me-1 small"></i> Booking Status
                    </label>
                    <select wire:model.live="filterBookingStatus"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All Status</option>
                        @foreach ($bookingStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ═══ Filter Row 2: Dates + Booking Type ═══ --}}
            <div class="row g-3 mt-1">
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar text-primary me-1 small"></i> From Date
                    </label>
                    <input type="date" wire:model.live="fromDate"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-calendar-check-o text-primary me-1 small"></i> To Date
                    </label>
                    <input type="date" wire:model.live="toDate"
                        class="form-control form-control-sm border-secondary-subtle shadow-sm">
                </div>
                <div class="col-md-4 col-lg">
                    <label class="form-label fw-medium">
                        <i class="fa fa-tag text-primary me-1 small"></i> Booking Type
                    </label>
                    <select wire:model.live="filterBookingType"
                        class="form-select form-select-sm border-secondary-subtle shadow-sm">
                        <option value="">All</option>
                        <option value="Long Term">Long Term</option>
                        <option value="Short Term">Short Term</option>
                        <option value="Commercial">Commercial</option>
                    </select>
                </div>
            </div>

            {{-- ═══ Reset Filters ═══ --}}
            @if ($this->activeFilterCount > 0)
                <div class="row mt-3">
                    <div class="col-12">
                        <button wire:click="resetFilters"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm">
                            <i class="fa fa-times"></i>
                            Reset Filters
                            <span class="badge bg-danger ms-1">{{ $this->activeFilterCount }}</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- ═══ Table Body ═══ --}}
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll"
                                        class="form-check-input shadow-sm" id="bookingSelectAllCheckbox" />
                                    <label class="form-check-label" for="bookingSelectAllCheckbox">
                                        @if ($columns['id'] ?? false)
                                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id"
                                                label="ID" />
                                        @endif
                                    </label>
                                </div>
                            </th>
                            @if ($columns['customer'] ?? false)
                                <th class="fw-semibold">Customer</th>
                            @endif
                            @if ($columns['group'] ?? false)
                                <th class="fw-semibold">Group</th>
                            @endif
                            @if ($columns['building'] ?? false)
                                <th class="fw-semibold">Building</th>
                            @endif
                            @if ($columns['property'] ?? false)
                                <th class="fw-semibold">Property No/Unit</th>
                            @endif
                            @if ($columns['start_date'] ?? false)
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="start_date" label="Start Date" /> </th>
                            @endif
                            @if ($columns['end_date'] ?? false)
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="end_date" label="End Date" /> </th>
                            @endif
                            @if ($columns['rent'] ?? false)
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="rent" :label="$config->amountLabel" /> </th>
                            @endif
                            @if ($columns['booking_status'] ?? false)
                                <th class="fw-semibold">Booking Status</th>
                            @endif
                            @if ($columns['created_at'] ?? false)
                                <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField"
                                        field="created_at" label="Created At" /> </th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}"
                                            wire:model.live="selected" class="form-check-input shadow-sm"
                                            id="bookingCheckbox{{ $item->id }}" />
                                        <label class="form-check-label" for="bookingCheckbox{{ $item->id }}">
                                            @if ($columns['id'] ?? false)
                                                <span
                                                    class="badge bg-light text-dark border">#{{ $item->id }}</span>
                                            @endif
                                        </label>
                                    </div>
                                </td>
                                @if ($columns['customer'] ?? false)
                                    <td>
                                        <i class="fa fa-user me-1 text-muted opacity-75"></i>
                                        {{ $item->customer?->name }}
                                    </td>
                                @endif
                                @if ($columns['group'] ?? false)
                                    <td>
                                        <i class="fa fa-folder-open me-1 text-muted opacity-75"></i>
                                        {{ $item->group?->name }}
                                    </td>
                                @endif
                                @if ($columns['building'] ?? false)
                                    <td>
                                        <i class="fa fa-building-o me-1 text-muted opacity-75"></i>
                                        {{ $item->building?->name }}
                                    </td>
                                @endif
                                @if ($columns['property'] ?? false)
                                    <td>
                                        @php
                                            if ($item->booking_status?->value == "created") {
                                                $url = $config->bookingEditRoute;
                                            } else {
                                                $url = $config->bookingViewRoute;
                                            }
                                        @endphp
                                        <a href="{{ route($url, $item->id) }}">
                                            <i class="fa fa-home me-1 text-muted opacity-75"></i>
                                            {{ $item->property?->number }}
                                        </a>
                                    </td>
                                @endif
                                @if ($columns['start_date'] ?? false)
                                    <td>
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                        {{ systemDate($item->start_date) }}
                                    </td>
                                @endif
                                @if ($columns['end_date'] ?? false)
                                    <td>
                                        <i class="fa fa-calendar me-1 text-muted opacity-75"></i>
                                        {{ systemDate($item->end_date) }}
                                    </td>
                                @endif
                                @if ($columns['rent'] ?? false)
                                    <td class="fw-medium">{{ currency($item->rent) }}</td>
                                @endif
                                @if ($columns['booking_status'] ?? false)
                                    <td>
                                        <span class="badge bg-{{ $item->booking_status?->color() }}">
                                            {{ $item->booking_status?->label() }}
                                        </span>
                                    </td>
                                @endif
                                @if ($columns['created_at'] ?? false)
                                    <td>
                                        <i
                                            class="fa fa-clock-o me-1 text-muted opacity-75"></i>{{ systemDateTime($item->created_at) }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ collect($columns)->filter()->count() + 1 }}"
                                    class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    {{ $config->bookingEmptyMessage }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($data->hasPages())
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
                    if (el && el.tomSelect) {
                        el.tomSelect.clear();
                        el.tomSelect.clearOptions();
                        el.tomSelect.load('');
                    }
                }

                // ── Cascade: Group → Building → Property ──
                $('#booking_filterGroup').on('change', function(e) {
                    @this.set('filterGroup', $(this).val() || '');
                    clearAndReload('booking_filterBuilding');
                    clearAndReload('booking_filterProperty');
                    @this.set('filterBuilding', '');
                    @this.set('filterProperty', '');
                });
                $('#booking_filterBuilding').on('change', function(e) {
                    @this.set('filterBuilding', $(this).val() || '');
                    clearAndReload('booking_filterProperty');
                    @this.set('filterProperty', '');
                });
                $('#booking_filterProperty').on('change', function(e) {
                    @this.set('filterProperty', $(this).val() || '');
                });
                $('#booking_filterCustomer').on('change', function(e) {
                    @this.set('filterCustomer', $(this).val() || '');
                });

                // ── Reset TomSelect on Livewire reset dispatch ──
                window.addEventListener('reset-booking-filters', () => {
                    ['booking_filterGroup', 'booking_filterBuilding', 'booking_filterProperty',
                        'booking_filterCustomer'
                    ].forEach(id => {
                        const el = document.getElementById(id);
                        if (el && el.tomSelect) {
                            el.tomSelect.clear();
                        }
                    });
                });

                // ── Table refresh event ──
                window.addEventListener('{{ $config->bookingRefreshTableEvent }}', event => {
                    Livewire.dispatch("{{ $config->bookingRefreshEvent }}");
                });

                // ── Initialize tooltips ──
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl, {
                        boundary: document.body
                    });
                });
            });
        </script>
    @endpush
</div>
