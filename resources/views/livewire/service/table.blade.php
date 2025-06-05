<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('service.create')
                        <a class="btn btn-primary d-flex align-items-center shadow-sm" href="{{ route('service::create') }}">
                            <i class="fa fa-plus me-2"></i>
                            Add New Service
                        </a>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('service.export')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                                <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                        @endcan
                        @can('service.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endcan
                        @can('service.import')
                            <button class="btn btn-info btn-sm d-flex align-items-center text-white" title="Import Services" data-bs-toggle="modal" data-bs-target="#ServiceImportModal">
                                <i class="fa fa-cloud-download me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Import</span>
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
                                <input type="text" wire:model.live="search" autofocus placeholder="Search services..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
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
                        <label for="department_id" class="form-label fw-medium">
                            <i class="fa fa-building text-primary me-1 small"></i>
                            Department
                        </label>
                        {{ html()->select('department_id', [])->value('')->class('select-department_id-list border-secondary-subtle shadow-sm')->id('department_id')->placeholder('All Departments') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label for="main_category_id" class="form-label fw-medium">
                            <i class="fa fa-folder text-primary me-1 small"></i>
                            Main Category
                        </label>
                        {{ html()->select('main_category_id', [])->value('')->class('select-category_id-list border-secondary-subtle shadow-sm')->id('main_category_id')->placeholder('All Categories') }}
                    </div>
                    <div class="col-md-3" wire:ignore>
                        <label for="sub_category_id" class="form-label fw-medium">
                            <i class="fa fa-folder-open text-primary me-1 small"></i>
                            Sub Category
                        </label>
                        {{ html()->select('sub_category_id', [])->value('')->class('select-category_id-list border-secondary-subtle shadow-sm')->id('sub_category_id')->placeholder('All Sub Categories') }}
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label fw-medium">
                            <i class="fa fa-toggle-on text-primary me-1 small"></i>
                            Status
                        </label>
                        {{ html()->select('status', activeOrDisabled())->value('')->class('form-select border-secondary-subtle shadow-sm')->placeholder('All Status')->id('status')->attribute('wire:model.live', 'status') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom mb-0">
                    <thead class="bg-light text-muted">
                        <tr class="text-capitalize small">
                            <th class="fw-semibold py-2">
                                <div class="form-check ms-1">
                                    <input type="checkbox" wire:model.live="selectAll" class="form-check-input shadow-sm" id="selectAllCheckbox" />
                                    <label class="form-check-label" for="selectAllCheckbox">
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="#" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="department_id" label="Department" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="main_category_id" label="Main Category" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sub_category_id" label="Sub Category" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="code" label="Code" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name_arabic" label="Arabic Name" /> </th>
                            <th class="fw-semibold text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mrp" label="Price" /> </th>
                            <th class="fw-semibold text-end"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="time" label="Duration" /> </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr>
                                <td>
                                    <div class="form-check ms-1">
                                        <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" class="form-check-input shadow-sm" id="checkbox{{ $item->id }}" />
                                        <label class="form-check-label" for="checkbox{{ $item->id }}">{{ $item->id }}</label>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark"><i class="fa fa-building-o me-1 opacity-50"></i>{{ $item->department?->name ?: '-' }}</span></td>
                                <td>
                                    @if ($item->mainCategory?->name)
                                        <i class="fa fa-folder me-1 text-warning opacity-75"></i>{{ $item->mainCategory->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->subCategory?->name)
                                        <i class="fa fa-folder-open-o me-1 text-muted opacity-75"></i>{{ $item->subCategory->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark border"><i class="fa fa-barcode me-1 opacity-50"></i>{{ $item->code }}</span></td>
                                <td>
                                    <a href="{{ route('service::edit', $item->id) }}" class="text-decoration-none fw-medium text-primary">
                                        <i class="fa fa-wrench me-1"></i>{{ $item->name }}
                                    </a>
                                </td>
                                <td dir="rtl" class="text-muted">{{ $item->name_arabic }}</td>
                                <td class="text-end fw-medium"><i class="fa fa-dollar text-success me-1"></i>{{ currency($item->mrp) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-info text-white"><i class="fa fa-clock-o me-1"></i>{{ $item->time }} min</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                {{ $data->links() }}
            </div>
        </div>

        <!-- Floating action button for mobile -->
        <div class="position-fixed bottom-0 end-0 mb-4 me-4 d-md-none">
            <a href="{{ route('service::create') }}" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-plus"></i>
            </a>
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

                    $('#department_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('department_id', value);
                    });
                    $('#main_category_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('main_category_id', value);
                    });
                    $('#sub_category_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('sub_category_id', value);
                    });
                });
            </script>
        @endpush
    </div>
