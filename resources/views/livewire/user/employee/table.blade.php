<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('employee.create')
                        <button class="btn btn-primary d-flex align-items-center shadow-sm" id="EmployeeAdd">
                            <i class="fa fa-plus me-2"></i>
                            Add New Employee
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('category.export')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                                <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                        @endcan
                        @can('employee.delete')
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
                                <input type="text" wire:model.live="search" autofocus placeholder="Search employees..." class="form-control border-secondary-subtle shadow-sm" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div>
                        <label for="role_id" class="form-label small fw-medium text-capitalize">
                            <i class="fa fa-lock me-1 text-muted"></i>
                            Role
                        </label>
                        <select wire:model.live="role_id" class="form-select shadow-sm border-secondary-subtle" id="role_id">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div>
                        <label for="is_active" class="form-label small fw-medium text-capitalize">
                            <i class="fa fa-toggle-on me-1 text-muted"></i>
                            Status
                        </label>
                        <select wire:model.live="is_active" class="form-select shadow-sm border-secondary-subtle" id="is_active">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
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
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-user me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" />
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-lock me-2 text-secondary small"></i>
                                    <span>Roles</span>
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-code me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="code" label="Code" />
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-envelope me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="email" label="Email" />
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-phone me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mobile" label="Mobile" />
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-map-marker me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="place" label="Place" />
                                </div>
                            </th>
                            <th class="border-0">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-flag me-2 text-secondary small"></i>
                                    <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="nationality" label="Nationality" />
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr class="align-middle">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="form-check ms-1">
                                            <input class="form-check-input" type="checkbox" value="{{ $item->id }}" wire:model.live="selected" id="employee-{{ $item->id }}" />
                                        </div>
                                        <span class="badge bg-secondary rounded-pill">{{ $item->id }}</span>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('users::employee::view', $item['id']) }}" class="text-decoration-none fw-semibold link-primary">
                                        {{ $item['name'] }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-info text-white">{{ getUserRoles($item) }}</span>
                                </td>
                                <td>
                                    <code class="bg-light rounded px-2 py-1 small">{{ $item->code }}</code>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $item->email }}</span>
                                </td>
                                <td>
                                    <span class="text-nowrap">{{ $item->mobile }}</span>
                                </td>
                                <td>
                                    <span class="text-secondary">{{ $item->place }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border border-secondary-subtle">{{ $item->nationality }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted my-4">
                                        <i class="fa fa-exclamation-circle fs-1 d-block mb-3 text-secondary-emphasis opacity-50"></i>
                                        <h5 class="fw-semibold mb-2">No Employees Found</h5>
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

        <!-- Mobile Floating Action Button -->
        <div class="d-block d-md-none position-fixed bottom-0 end-0 mb-4 me-4" style="z-index: 1050;">
            <div class="dropup">
                <button class="btn btn-primary rounded-circle shadow-lg p-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="mobileActionButton">
                    <i class="fa fa-cog fs-4"></i>
                </button>
                <ul class="dropdown-menu shadow-lg" aria-labelledby="mobileActionButton">
                    @can('employee.create')
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" id="MobileEmployeeAdd">
                                <i class="fa fa-plus me-2 text-success"></i>
                                Add New Employee
                            </a>
                        </li>
                    @endcan
                    @can('category.export')
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" wire:click="export()">
                                <i class="fa fa-file-excel-o me-2 text-success"></i>
                                Export Employees
                            </a>
                        </li>
                    @endcan
                    @can('employee.delete')
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="#" wire:click="delete()" wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-2 text-danger"></i>
                                Delete Selected
                            </a>
                        </li>
                    @endcan
                </ul>
            </div>
        </div>

        @push('scripts')
            <script>
                $(document).ready(function() {
                    // Initialize tooltips
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Employee-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });

                    $('#EmployeeAdd, #MobileEmployeeAdd').click(function() {
                        Livewire.dispatch("Employee-Page-Create-Component");
                    });

                    window.addEventListener('RefreshEmployeeTable', event => {
                        Livewire.dispatch("Employee-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
