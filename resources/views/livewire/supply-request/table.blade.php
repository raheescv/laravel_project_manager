<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row g-3">
                <div class="col-md-4 d-flex flex-wrap gap-2 align-items-center">
                    @can('supply request.create')
                        <a href="{{ route($type === 'Return' ? 'supply-return::create' : 'supply-request::create') }}"
                            class="btn btn-primary btn-sm hstack gap-2 shadow-sm">
                            <i class="demo-psi-add fs-5"></i><span class="vr"></span> {{ $type === 'Return' ? 'New Return' : 'New Request' }}
                        </a>
                    @endcan
                    @can('supply request.delete')
                        <button class="btn btn-sm btn-outline-danger" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?" x-show="$wire.selected.length > 0" style="display: none;">
                            <i class="demo-pli-recycling me-1"></i> Delete
                        </button>
                    @endcan
                </div>
                <div class="col-md-8">
                    <div class="gap-2 d-flex justify-content-md-end align-items-center">
                        <div class="form-group">
                            <select wire:model.live="limit" class="form-select form-select-sm">
                                <option value="10">10 rows</option>
                                <option value="50">50 rows</option>
                                <option value="100">100 rows</option>
                                <option value="500">500 rows</option>
                            </select>
                        </div>
                        <div class="form-group" style="width: 250px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text border-end-0">
                                    <i class="demo-pli-magnifi-glass"></i>
                                </span>
                                <input type="text" wire:model.live.debounce.500ms="search" class="form-control border-start-0"
                                    placeholder="Search order, person, property..." autofocus>
                            </div>
                        </div>
                        {{-- Column Visibility Dropdown --}}
                        <div class="btn-group shadow-sm">
                            <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                aria-expanded="false" title="Column Visibility">
                                <i class="demo-pli-view-list fs-5"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 200px;" onclick="event.stopPropagation()">
                                <li class="dropdown-header fw-semibold">Show / Hide Columns</li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                @foreach ($visibleColumnNames as $column => $label)
                                    <li>
                                        <div class="form-check px-3 py-1">
                                            <input class="form-check-input" type="checkbox" wire:model.lazy="visibleColumns.{{ $column }}"
                                                id="col_{{ $column }}" onclick="event.stopPropagation()">
                                            <label class="form-check-label small" for="col_{{ $column }}" onclick="event.stopPropagation()">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    </li>
                                @endforeach
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <button class="dropdown-item small text-center text-muted" wire:click="resetColumnVisibility"
                                        onclick="event.stopPropagation()">
                                        <i class="fa fa-refresh me-1"></i> Reset to Default
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Panel --}}
            <hr class="mt-3 mb-0">
            <div class="mt-3 col-12">
                <div class="p-3 rounded bg-light">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small" for="from_date">
                                <i class="demo-psi-calendar-4 me-1"></i> From Date
                            </label>
                            <input type="date" wire:model.live="from_date" class="form-control form-control-sm" id="from_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="to_date">
                                <i class="demo-psi-calendar-4 me-1"></i> To Date
                            </label>
                            <input type="date" wire:model.live="to_date" class="form-control form-control-sm" id="to_date">
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label small" for="branch_id">
                                <i class="demo-psi-home me-1"></i> Branch
                            </label>
                            {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small" for="status">
                                <i class="fa fa-flag me-1"></i> Status
                            </label>
                            <select wire:model.live="status" class="form-select form-select-sm" id="status">
                                <option value="">All Statuses</option>
                                @foreach (\App\Enums\SupplyRequest\SupplyRequestStatus::values() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label small" for="sr_property_group_id">
                                <i class="fa fa-object-group me-1"></i> Property Group
                            </label>
                            {{ html()->select('property_group_id', [])->value($this->property_group_id)->class('select-property_group_id-list')->id('sr_property_group_id')->placeholder('All Groups') }}
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label small" for="sr_property_building_id">
                                <i class="fa fa-building-o me-1"></i> Building
                            </label>
                            {{ html()->select('property_building_id', [])->value($this->property_building_id)->class('select-property_building_id-list')->id('sr_property_building_id')->placeholder('All Buildings')->attribute('data-group-select', '#sr_property_group_id') }}
                        </div>
                        <div class="col-md-6" wire:ignore>
                            <label class="form-label small" for="property_id">
                                <i class="fa fa-building me-1"></i> Property
                            </label>
                            {{ html()->select('property_id', [])->value($this->property_id)->class('select-property_id-list')->id('property_id')->placeholder('All Properties') }}
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label small" for="created_by">
                                <i class="fa fa-user me-1"></i> Created By
                            </label>
                            {{ html()->select('created_by', [])->value($this->created_by)->class('select-user_id-list')->id('created_by')->placeholder('All Users') }}
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label small" for="approved_by">
                                <i class="fa fa-user-check me-1"></i> Approved By
                            </label>
                            {{ html()->select('approved_by', [])->value($this->approved_by)->class('select-user_id-list')->id('approved_by')->placeholder('All Users') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0">
                    <thead class="bg-light">
                        <tr class="text-capitalize text-nowrap">
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                            </th>
                            @if ($visibleColumns['id'] ?? true)
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.id" label="#" />
                                </th>
                            @endif
                            @if ($visibleColumns['date'] ?? true)
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.date" label="Date" />
                                </th>
                            @endif
                            @if ($visibleColumns['order_no'] ?? true)
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.order_no" label="Order No" />
                                </th>
                            @endif
                            @if ($visibleColumns['type'] ?? true)
                                <th class="fw-semibold">Type</th>
                            @endif
                            @if ($visibleColumns['property'] ?? true)
                                <th class="fw-semibold">Property</th>
                            @endif
                            @if ($visibleColumns['property_group'] ?? false)
                                <th class="fw-semibold">Group</th>
                            @endif
                            @if ($visibleColumns['property_building'] ?? false)
                                <th class="fw-semibold">Building</th>
                            @endif
                            @if ($visibleColumns['property_type'] ?? false)
                                <th class="fw-semibold">Type</th>
                            @endif
                            @if ($visibleColumns['requested_by'] ?? true)
                                <th class="fw-semibold">Requested By</th>
                            @endif
                            @if ($visibleColumns['items'] ?? true)
                                <th class="fw-semibold">Items</th>
                            @endif
                            @if ($visibleColumns['grand_total'] ?? true)
                                <th class="fw-semibold text-end">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.grand_total"
                                        label="Grand Total" />
                                </th>
                            @endif
                            @if ($visibleColumns['status'] ?? true)
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.status" label="Status" />
                                </th>
                            @endif
                            @if ($visibleColumns['created_by'] ?? true)
                                <th class="fw-semibold">Created By</th>
                            @endif
                            @if ($visibleColumns['approved_by'] ?? false)
                                <th class="fw-semibold">Approved By</th>
                            @endif
                            @if ($visibleColumns['created_at'] ?? true)
                                <th class="fw-semibold">
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.created_at"
                                        label="Created At" />
                                </th>
                            @endif
                            <th class="fw-semibold text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $item->id }}">
                                </td>
                                @if ($visibleColumns['id'] ?? true)
                                    <td>
                                        <span class="text-muted">#{{ $item->id }}</span>
                                    </td>
                                @endif
                                @if ($visibleColumns['date'] ?? true)
                                    <td class="text-nowrap">
                                        <span>{{ systemDate($item->date) }}</span>
                                    </td>
                                @endif
                                @if ($visibleColumns['order_no'] ?? true)
                                    <td>
                                        <a href="{{ route('supply-request::edit', $item->id) }}" class="fw-medium text-decoration-none">
                                            {{ $item->order_no }}
                                        </a>
                                    </td>
                                @endif
                                @if ($visibleColumns['type'] ?? true)
                                    <td>
                                        @if ($item->type === 'Add')
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <i class="fa fa-plus-circle me-1"></i> Add
                                            </span>
                                        @else
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <i class="fa fa-undo me-1"></i> Return
                                            </span>
                                        @endif
                                    </td>
                                @endif
                                @if ($visibleColumns['property'] ?? true)
                                    <td class="text-nowrap">{{ $item->property?->number ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['property_group'] ?? false)
                                    <td class="text-nowrap">{{ $item->property?->group?->name ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['property_building'] ?? false)
                                    <td class="text-nowrap">{{ $item->property?->building?->name ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['property_type'] ?? false)
                                    <td class="text-nowrap">{{ $item->property?->type?->name ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['requested_by'] ?? true)
                                    <td class="text-nowrap">{{ $item->contact_person ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['items'] ?? true)
                                    <td class="text-center">{{ $item->items_count }}</td>
                                @endif
                                @if ($visibleColumns['grand_total'] ?? true)
                                    <td class="text-end fw-bold text-nowrap">{{ currency($item->grand_total) }}</td>
                                @endif
                                @if ($visibleColumns['status'] ?? true)
                                    <td>
                                        <span
                                            class="badge bg-{{ $item->status?->color() ?? 'secondary' }} bg-opacity-10 text-{{ $item->status?->color() ?? 'secondary' }}">
                                            {{ $item->status?->label() ?? '-' }}
                                        </span>
                                    </td>
                                @endif
                                @if ($visibleColumns['created_by'] ?? true)
                                    <td class="text-nowrap">{{ $item->creator?->name ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['approved_by'] ?? false)
                                    <td class="text-nowrap">{{ $item->approver?->name ?? '-' }}</td>
                                @endif
                                @if ($visibleColumns['created_at'] ?? true)
                                    <td class="text-nowrap">
                                        <span>{{ systemDateTime($item->created_at) }}</span>
                                    </td>
                                @endif
                                <td class="text-end text-nowrap pe-3">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('supply request.edit')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('supply-request::edit', $item->id) }}">
                                                        <i class="demo-pli-file-edit me-2"></i> Edit / View
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('supply request.print')
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('supply-request::print', $item->id) }}" target="_blank">
                                                        <i class="fa fa-print me-2"></i> Print
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="20" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="demo-psi-file fs-1 d-block mb-2 opacity-25"></i>
                                        No supply requests found
                                    </div>
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
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    @this.set('branch_id', $(this).val() || null);
                });
                $('#property_id').on('change', function(e) {
                    @this.set('property_id', $(this).val() || null);
                });
                $('#sr_property_group_id').on('change', function(e) {
                    @this.set('property_group_id', $(this).val() || null);
                });
                $('#sr_property_building_id').on('change', function(e) {
                    @this.set('property_building_id', $(this).val() || null);
                });
                $('#sr_property_type_id').on('change', function(e) {
                    @this.set('property_type_id', $(this).val() || null);
                });
                $('#created_by').on('change', function(e) {
                    @this.set('created_by', $(this).val() || null);
                });
                $('#approved_by').on('change', function(e) {
                    @this.set('approved_by', $(this).val() || null);
                });
            });
        </script>
    @endpush
</div>
