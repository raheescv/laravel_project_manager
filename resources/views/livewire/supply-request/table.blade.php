<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="row g-3">
                <div class="col-md-4 d-flex flex-wrap gap-2 align-items-center">
                    @can('supply request.create')
                        <a href="{{ route('supply-request::create') }}" class="btn btn-primary btn-sm hstack gap-2 shadow-sm">
                            <i class="demo-psi-add fs-5"></i><span class="vr"></span> New Request
                        </a>
                    @endcan
                    @can('supply request.delete')
                        <button class="btn btn-sm btn-outline-danger" wire:click="delete()"
                            wire:confirm="Are you sure you want to delete the selected items?"
                            x-show="$wire.selected.length > 0" style="display: none;">
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
                    </div>
                </div>
            </div>

            {{-- Filter Panel --}}
            <hr class="mt-3 mb-0">
            <div class="mt-3 col-12">
                <div class="p-3 rounded bg-light">
                    <div class="row g-3">
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
                        <div class="col-md-3">
                            <label class="form-label small" for="type">
                                <i class="fa fa-exchange me-1"></i> Type
                            </label>
                            <select wire:model.live="type" class="form-select form-select-sm" id="type">
                                <option value="">All Types</option>
                                <option value="Add">Add</option>
                                <option value="Return">Return</option>
                            </select>
                        </div>
                        <div class="col-md-3" wire:ignore>
                            <label class="form-label small" for="property_id">
                                <i class="fa fa-building me-1"></i> Property
                            </label>
                            {{ html()->select('property_id', [])->value($this->property_id)->class('select-property_id-list')->id('property_id')->placeholder('All Properties') }}
                        </div>
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
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible;">
                <table class="table table-hover align-middle border-bottom mb-0 table-sm table-striped">
                    <thead class="bg-light text-nowrap">
                        <tr class="small">
                            <th class="ps-3" style="width: 40px;">
                                <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                            </th>
                            <th class="fw-semibold">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.id" label="#" />
                            </th>
                            <th class="fw-semibold">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.date" label="Date" />
                            </th>
                            <th class="fw-semibold">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.order_no" label="Order No" />
                            </th>
                            <th class="fw-semibold">Type</th>
                            <th class="fw-semibold">Property</th>
                            <th class="fw-semibold">Requested By</th>
                            <th class="fw-semibold">Items</th>
                            <th class="fw-semibold text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.grand_total" label="Grand Total" />
                            </th>
                            <th class="fw-semibold">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.status" label="Status" />
                            </th>
                            <th class="fw-semibold">Created By</th>
                            <th class="fw-semibold">Approved By</th>
                            <th class="fw-semibold">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="supply_requests.created_at" label="Created At" />
                            </th>
                            <th class="fw-semibold text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input" wire:model.live="selected" value="{{ $item->id }}">
                                </td>
                                <td>
                                    <span class="text-muted">#{{ $item->id }}</span>
                                </td>
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                        <span>{{ systemDate($item->date) }}</span>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('supply-request::edit', $item->id) }}" class="fw-medium text-decoration-none">
                                        {{ $item->order_no }}
                                    </a>
                                </td>
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
                                <td class="text-nowrap">{{ $item->property?->number ?? '-' }}</td>
                                <td class="text-nowrap">{{ $item->contact_person ?? '-' }}</td>
                                <td class="text-center">{{ $item->items_count }}</td>
                                <td class="text-end fw-bold text-nowrap">{{ currency($item->grand_total) }}</td>
                                <td>
                                    <span class="badge bg-{{ $item->status?->color() ?? 'secondary' }} bg-opacity-10 text-{{ $item->status?->color() ?? 'secondary' }}">
                                        {{ $item->status?->label() ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-nowrap">{{ $item->creator?->name ?? '-' }}</td>
                                <td class="text-nowrap">{{ $item->approver?->name ?? '-' }}</td>
                                <td class="text-nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                        <span>{{ systemDateTime($item->created_at) }}</span>
                                    </div>
                                </td>
                                <td class="text-end text-nowrap">
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
                                <td colspan="14" class="text-center py-4">
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
