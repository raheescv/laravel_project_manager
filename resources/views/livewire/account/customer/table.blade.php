<div>
    <div class="card shadow-sm">
        <div class="card-header bg-light py-3">
            <div class="row mt-3">
                <div class="col-md-6 d-flex flex-wrap gap-2 align-items-center mb-3 mb-md-0">
                    @can('customer.create')
                        <button class="btn btn-primary d-flex align-items-center shadow-sm" id="CustomerAdd">
                            <i class="fa fa-user-plus me-2"></i>
                            Add New Customer
                        </button>
                    @endcan
                    <div class="btn-group shadow-sm">
                        @can('customer.export')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export()">
                                <i class="fa fa-file-excel-o me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                        @endcan
                        @can('customer.delete')
                            <button class="btn btn-danger btn-sm d-flex align-items-center" title="Delete Selected" data-bs-toggle="tooltip" wire:click="delete()"
                                wire:confirm="Are you sure you want to delete the selected items?">
                                <i class="fa fa-trash me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Delete</span>
                            </button>
                        @endcan
                        @can('customer.import')
                            <button class="btn btn-info btn-sm d-flex align-items-center text-white" title="Import Customers" data-bs-toggle="modal" data-bs-target="#CustomerImportModal">
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
                                <input type="text" wire:model.live="search" autofocus placeholder="Search customers..." class="form-control form-control-sm border-secondary-subtle shadow-sm"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="my-3">
            <div class="col-lg-12">
                <div class="row g-3">
                    <div class="col-md-6" wire:ignore>
                        <label for="customer_type_id" class="form-label fw-medium">
                            <i class="fa fa-tag text-primary me-1 small"></i>
                            Customer Type
                        </label>
                        {{ html()->select('customer_type_id', [])->value('')->class('select-customer_type-id-list border-secondary-subtle shadow-sm')->id('customer_type_id')->placeholder('All Customer Types')->attribute('wire:model', 'customer_type_id') }}
                    </div>
                    <div class="col-md-6" wire:ignore>
                        <label for="nationality" class="form-label fw-medium">
                            <i class="fa fa-flag text-primary me-1 small"></i>
                            Nationality
                        </label>
                        {{ html()->select('nationality', $countries)->value('')->class('tomSelect border-secondary-subtle shadow-sm')->id('table_nationality')->placeholder('All Nationalities')->attribute('wire:model', 'nationality') }}
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
                                        <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id" label="ID" />
                                    </label>
                                </div>
                            </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer_type_id" label="Type" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="name" label="Name" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="mobile" label="Mobile" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="whatsapp_mobile" label="WhatsApp" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="email" label="Email" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="dob" label="DOB" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="id_no" label="ID No." /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="nationality" label="Nationality" /> </th>
                            <th class="fw-semibold"> <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="company" label="Company" /> </th>
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
                                <td><span class="badge bg-light text-dark border"><i class="fa fa-tag me-1 opacity-50"></i>{{ $item->customerType?->name ?: '-' }}</span></td>
                                <td>
                                    <a href="{{ route('account::customer::view', $item->id) }}" class="text-decoration-none fw-medium text-primary">
                                        <i class="fa fa-user me-1"></i>{{ $item->name }}
                                    </a>
                                </td>
                                <td><i class="fa fa-phone me-1 text-success opacity-75"></i>{{ $item->mobile ?: '-' }}</td>
                                <td>
                                    @if ($item->whatsapp_mobile)
                                        <i class="fa fa-whatsapp me-1 text-success"></i>{{ $item->whatsapp_mobile }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->email)
                                        <i class="fa fa-envelope-o me-1 text-muted"></i>{{ $item->email }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->dob)
                                        <i class="fa fa-calendar me-1 text-muted"></i>{{ systemDate($item->dob) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->id_no)
                                        <i class="fa fa-id-card-o me-1 text-muted"></i>{{ $item->id_no }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->nationality)
                                        <i class="fa fa-flag me-1 text-muted"></i>{{ $item->nationality }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($item->company)
                                        <i class="fa fa-building-o me-1 text-muted"></i>{{ $item->company }}
                                    @else
                                        -
                                    @endif
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
            <button id="CustomerAddMobile" class="btn btn-primary rounded-circle shadow btn-lg">
                <i class="fa fa-user-plus"></i>
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

                    $('#table_nationality').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('nationality', value);
                    });

                    $('#customer_type_id').on('change', function(e) {
                        const value = $(this).val() || null;
                        @this.set('customer_type_id', value);
                    });

                    $(document).on('click', '.edit', function() {
                        Livewire.dispatch("Customer-Page-Update-Component", {
                            id: $(this).attr('table_id')
                        });
                    });

                    $('#CustomerAdd, #CustomerAddMobile').click(function() {
                        Livewire.dispatch("Customer-Page-Create-Component");
                    });

                    window.addEventListener('RefreshCustomerTable', event => {
                        Livewire.dispatch("Customer-Refresh-Component");
                    });
                });
            </script>
        @endpush
    </div>
