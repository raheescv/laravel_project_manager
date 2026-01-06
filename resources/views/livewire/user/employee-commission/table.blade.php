<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('employee commission.create')
                        <button class="btn btn-primary d-flex align-items-center shadow-sm" id="EmployeeCommissionAdd">
                            <i class="fa fa-plus me-2"></i>
                            Add New
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('employee commission.delete')
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
                                <input type="text" wire:model.live="search" autofocus placeholder="Search by employee or product name..." class="form-control border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <div wire:ignore>
                        <label for="employee_id" class="form-label small fw-medium text-capitalize">
                            <i class="fa fa-user me-1 text-muted"></i>
                            Employee
                        </label>
                        {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list border-primary-subtle')->id('employee_id')->attribute('style', 'width:100%')->placeholder('Search and select employee...') }}
                    </div>
                </div>
                <div class="col-md-6">
                    <div wire:ignore>
                        <label for="product_id" class="form-label small fw-medium text-capitalize">
                            <i class="fa fa-box me-1 text-muted"></i>
                            Product
                        </label>
                        {{ html()->select('product_id', [])->value('')->class('select-product_id-list border-success-subtle')->id('product_id')->attribute('style', 'width:100%')->placeholder('Search and select product...') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0">
                    <thead class="table-light text-capitalize">
                        <tr>
                            <th class="border-0">
                                <div class="form-check ms-1">
                                    <input class="form-check-input" type="checkbox" wire:model.live="selectAll" id="selectAll" />
                                    <label class="form-check-label" for="selectAll">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="employee_commissions.id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="users.name" label="Employee" />
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-tags me-2 text-secondary small"></i>
                                    <span>Product Category</span>
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-building me-2 text-secondary small"></i>
                                    <span>Department</span>
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-box me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="products.name" label="Product" />
                                </div>
                            </th>
                            <th class="border-0 text-end">
                                <div class="text-end">
                                    <i class="fa fa-percent me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="commission_percentage" label="Commission %" />
                                </div>
                            </th>
                            <th class="border-0" width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check ms-1">
                                            <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected" id="commission-{{ $item->id }}" />
                                        </div>
                                        <span class="badge bg-secondary rounded-pill">{{ $item->id }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $item->employee->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ $item->product->mainCategory->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $item->product->department->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $item->product->name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-success">{{ number_format($item->commission_percentage, 2) }}%</span>
                                </td>
                                <td>
                                    @can('employee commission.edit')
                                        <i table_id="{{ $item->id }}" class="fa fa-pencil fs-5 me-2 pointer edit text-primary" style="cursor: pointer;" title="Edit"></i>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted my-4">
                                        <i class="fa fa-exclamation-circle fs-1 d-block mb-3 text-secondary-emphasis opacity-50"></i>
                                        <h5 class="fw-semibold mb-2">No Commissions Found</h5>
                                        <p class="mb-0">Try adjusting your search criteria</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top bg-light">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-2 mb-lg-0">
                        <p class="text-muted small mb-0">
                            Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ $data->total() }} entries
                        </p>
                    </div>
                    <div class="col-lg-6 d-flex justify-content-lg-end">
                        {{ $data->links() }}
                    </div>
                </div>
            </div>
        </div>
        @push('scripts')
            <script>
                $(document).ready(function() {
                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("EmployeeCommission-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });

                    $('#EmployeeCommissionAdd, #MobileEmployeeCommissionAdd').click(function() {
                        Livewire.dispatch("EmployeeCommission-Page-Create-Component");
                    });

                    window.addEventListener('RefreshEmployeeCommissionTable', event => {
                        Livewire.dispatch("EmployeeCommission-Refresh-Component");
                    });
                    $('#employee_id').on('change', function(e) {
                        @this.set('employee_id', $(this).val());
                    });
                    $('#product_id').on('change', function(e) {
                        @this.set('product_id', $(this).val());
                    });
                });
            </script>
        @endpush
    </div>
</div>
