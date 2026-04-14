<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('property.create')
                        <button class="btn btn-primary d-flex align-items-center shadow-sm" id="PropertyAdd">
                            <i class="fa fa-plus-circle me-2"></i>
                            Add New Property
                        </button>
                        <a href="{{ route('property::property::import') }}" class="btn btn-info d-flex align-items-center shadow-sm text-white">
                            <i class="fa fa-cloud-upload me-2"></i>
                            Import
                        </a>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('property.view')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                                <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                        @endcan
                        @can('property.delete')
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
                                <input type="text" wire:model.live="search" autofocus placeholder="Search properties..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="col-lg-12">
                <div class="row g-3">
                    <div class="col-md-3" wire:ignore>
                        <label for="filterGroup" class="form-label fw-medium">
                            <i class="fa fa-object-group text-primary me-1 small"></i>
                            Group / Project
                        </label>
                        <select class="form-select form-select-sm select-filter-property_group_id border-secondary-subtle shadow-sm" id="filterGroup">
                            <option value="">All Groups</option>
                            @foreach(\App\Models\PropertyGroup::orderBy('name')->get() as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label for="filterBuilding" class="form-label fw-medium">
                            <i class="fa fa-building text-primary me-1 small"></i>
                            Building
                        </label>
                        <select class="form-select form-select-sm select-filter-property_building_id border-secondary-subtle shadow-sm" id="filterBuilding">
                            <option value="">All Buildings</option>
                            @foreach(\App\Models\PropertyBuilding::orderBy('name')->get() as $building)
                                <option value="{{ $building->id }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2" wire:ignore>
                        <label for="filterType" class="form-label fw-medium">
                            <i class="fa fa-tags text-primary me-1 small"></i>
                            Type
                        </label>
                        <select class="form-select form-select-sm select-filter-property_type_id border-secondary-subtle shadow-sm" id="filterType">
                            <option value="">All Types</option>
                            @foreach(\App\Models\PropertyType::orderBy('name')->get() as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filterStatus" class="form-label fw-medium">
                            <i class="fa fa-circle text-primary me-1 small"></i>
                            Status
                        </label>
                        {{ html()->select('filterStatus', propertyStatusOptions())->value($filterStatus)->class('form-select form-select-sm border-secondary-subtle shadow-sm')->id('filterStatus')->attribute('wire:model.live', 'filterStatus')->placeholder('All Status') }}
                    </div>
                    <div class="col-md-2">
                        <label for="filterOwnership" class="form-label fw-medium">
                            <i class="fa fa-key text-primary me-1 small"></i>
                            Ownership
                        </label>
                        <select class="form-select form-select-sm border-secondary-subtle shadow-sm" wire:model.live="filterOwnership" id="filterOwnership">
                            <option value="">All</option>
                            <option value="Owner">Owner</option>
                            <option value="Tenant">Tenant</option>
                        </select>
                    </div>
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
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="number" /> </th>
                            <th class="fw-semibold">Type</th>
                            <th class="fw-semibold">Group</th>
                            <th class="fw-semibold">Building</th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="floor" label="Floor" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="rent" label="Rent" /> </th>
                            <th class="fw-semibold">Ownership</th>
                            <th class="fw-semibold">Kahramaa</th>
                            <th class="fw-semibold">Parking</th>
                            <th class="fw-semibold">Status</th>
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
                                    <span class="fw-medium text-dark">
                                        <i class="fa fa-home me-1 text-primary opacity-75"></i>{{ $item->number }}
                                    </span>
                                </td>
                                <td>
                                    @if($item->type?->name)
                                        <span class="badge bg-light text-dark border"><i class="fa fa-tag me-1 opacity-50"></i>{{ $item->type->name }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->building?->group?->name)
                                        <i class="fa fa-object-group me-1 text-muted"></i>{{ $item->building->group->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->building?->name)
                                        <i class="fa fa-building me-1 text-muted"></i>{{ $item->building->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->floor)
                                        <i class="fa fa-bars me-1 text-muted"></i>{{ $item->floor }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <i class="fa fa-money me-1 text-success opacity-75"></i>{{ number_format($item->rent, 2) }}
                                </td>
                                <td>
                                    @if($item->ownership)
                                        <i class="fa fa-key me-1 text-muted"></i>{{ $item->ownership }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->kahramaa)
                                        <i class="fa fa-bolt me-1 text-warning"></i>{{ $item->kahramaa }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->parking)
                                        <i class="fa fa-car me-1 text-muted"></i>{{ $item->parking }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($item->status)
                                        <span class="badge bg-{{ $item->status === \App\Enums\Property\PropertyStatus::Occupied ? 'danger' : ($item->status === \App\Enums\Property\PropertyStatus::Vacant ? 'success' : ($item->status === \App\Enums\Property\PropertyStatus::Booked ? 'warning' : 'info')) }}">
                                            {{ $item->status->label() }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        @can('property.edit')
                                            <button table_id="{{ $item->id }}" class="btn btn-light btn-sm edit" title="View / Edit" data-bs-toggle="tooltip">
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-5 text-muted">
                                    <i class="fa fa-building-o fa-3x mb-3 d-block opacity-25"></i>
                                    No properties found matching your search.
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

        <!-- Floating action button for mobile -->
        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none">
            <button id="PropertyAddMobile" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </button>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    // Initialize tooltips
                    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.map(function(tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl, {
                            boundary: document.body
                        });
                    });

                    // Filter TomSelect change handlers
                    $('#filterGroup').on('change', function() {
                        @this.set('filterGroup', $(this).val());
                    });
                    $('#filterBuilding').on('change', function() {
                        @this.set('filterBuilding', $(this).val());
                    });
                    $('#filterType').on('change', function() {
                        @this.set('filterType', $(this).val());
                    });

                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Property-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });

                    $('#PropertyAdd, #PropertyAddMobile').click(function() {
                        Livewire.dispatch("Property-Page-Create-Component");
                    });

                    window.addEventListener('RefreshPropertyTable', event => {
                        Livewire.dispatch("Property-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
</div>
