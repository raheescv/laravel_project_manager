<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6 d-flex gap-2">
                @can('property.create')
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="PropertyAdd">
                        <i class="demo-psi-add fs-5"></i>
                        <span>Add New</span>
                    </button>
                @endcan
                @can('property.delete')
                    @if(count($selected) > 0)
                        <button class="btn btn-outline-danger d-inline-flex align-items-center gap-2" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                            <i class="demo-pli-recycling fs-5"></i>
                            <span>Delete</span>
                        </button>
                    @endif
                @endcan
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <div class="d-flex bg-light rounded-2 px-2">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-list-view fs-6"></i>
                        </span>
                        <select wire:model.live="limit" class="form-select bg-transparent border-0 fw-semibold py-2" style="width: 80px; box-shadow: none; font-size: 0.875rem;">
                            <option value="10">10</option>
                            <option value="100">100</option>
                            <option value="500">500</option>
                        </select>
                    </div>
                    <div class="d-flex bg-light rounded-2 px-2 flex-grow-1 flex-md-grow-0" style="min-width: 250px;">
                        <span class="d-flex align-items-center text-muted">
                            <i class="demo-psi-magnifi-glass fs-6"></i>
                        </span>
                        <input type="text" wire:model.live="search" autofocus placeholder="Search properties..." class="form-control bg-transparent border-0 py-2" style="box-shadow: none; font-size: 0.875rem;" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Row --}}
    <div class="card-body border-bottom py-3">
        <div class="row g-3">
            <div class="col-md-2" wire:ignore>
                <label class="form-label fw-semibold small text-muted mb-1">Group/Project</label>
                <select class="form-select form-select-sm select-filter-property_group_id" id="filterGroup">
                    <option value="">All</option>
                    @foreach(\App\Models\PropertyGroup::orderBy('name')->get() as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" wire:ignore>
                <label class="form-label fw-semibold small text-muted mb-1">Building</label>
                <select class="form-select form-select-sm select-filter-property_building_id" id="filterBuilding">
                    <option value="">All</option>
                    @foreach(\App\Models\PropertyBuilding::orderBy('name')->get() as $building)
                        <option value="{{ $building->id }}">{{ $building->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" wire:ignore>
                <label class="form-label fw-semibold small text-muted mb-1">Type</label>
                <select class="form-select form-select-sm select-filter-property_type_id" id="filterType">
                    <option value="">All</option>
                    @foreach(\App\Models\PropertyType::orderBy('name')->get() as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small text-muted mb-1">Status</label>
                <select class="form-select form-select-sm" wire:model.live="filterStatus">
                    <option value="">All</option>
                    @foreach(\App\Enums\Property\PropertyStatus::cases() as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small text-muted mb-1">Availability Status</label>
                <select class="form-select form-select-sm" wire:model.live="filterAvailabilityStatus">
                    <option value="">All</option>
                    <option value="available">Available</option>
                    <option value="sold">Sold</option>
                    <option value="reserved">Reserved</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label fw-semibold small text-muted mb-1">Flag</label>
                <select class="form-select form-select-sm" wire:model.live="filterFlag">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="disabled">Disabled</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label fw-semibold small text-muted mb-1">Ownership</label>
                <select class="form-select form-select-sm" wire:model.live="filterOwnership">
                    <option value="">All</option>
                    <option value="Owner">Owner</option>
                    <option value="Tenant">Tenant</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" width="50">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAll">
                            </div>
                        </th>
                        <th> <x-sortable-header field="name" label="Name" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th>Type</th>
                        <th>Group</th>
                        <th>Building</th>
                        <th> <x-sortable-header field="floor" label="Floor" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th> <x-sortable-header field="rent" label="Rent" :sortField="$sortField" :direction="$sortDirection" /> </th>
                        <th>Ownership</th>
                        <th>Kahramaa</th>
                        <th>Parking</th>
                        <th>Status</th>
                        <th>Availability Status</th>
                        <th width="8%" class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $item)
                        <tr>
                            <td class="ps-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected">
                                </div>
                            </td>
                            <td class="fw-semibold text-dark">{{ $item->name }}</td>
                            <td>{{ $item->type?->name }}</td>
                            <td>{{ $item->building?->group?->name }}</td>
                            <td>{{ $item->building?->name }}</td>
                            <td>{{ $item->floor }}</td>
                            <td>{{ number_format($item->rent, 2) }}</td>
                            <td>{{ $item->ownership }}</td>
                            <td>{{ $item->kahramaa }}</td>
                            <td>{{ $item->parking }}</td>
                            <td>
                                @if($item->status)
                                    <span class="badge bg-{{ $item->status === \App\Enums\Property\PropertyStatus::Occupied ? 'danger' : ($item->status === \App\Enums\Property\PropertyStatus::Vacant ? 'success' : ($item->status === \App\Enums\Property\PropertyStatus::Booked ? 'warning' : 'info')) }}">
                                        {{ $item->status->label() }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ ucfirst($item->availability_status ?? '') }}</td>
                            <td class="text-end pe-4">
                                @can('property.edit')
                                    <button table_id="{{ $item->id }}" class="btn btn-icon btn-sm btn-hover btn-light edit" title="Edit Property">
                                        <i class="demo-psi-pencil fs-5 text-muted"></i>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-5 text-muted">No properties found matching your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($data->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $data->links() }}
        </div>
    @endif

    @push('scripts')
        <script>
            $(document).ready(function() {
                $(document).on('click', '.edit', function() {
                    Livewire.dispatch("Property-Page-Update-Component", {
                        id: $(this).attr('table_id')
                    });
                });
                $('#PropertyAdd').click(function() {
                    Livewire.dispatch("Property-Page-Create-Component");
                });
                window.addEventListener('RefreshPropertyTable', event => {
                    Livewire.dispatch("Property-Refresh-Component");
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
            });
        </script>
    @endpush
</div>
