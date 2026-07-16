<div>
    <div class="modal-header bg-info bg-gradient text-white">
        <h1 class="modal-title fs-5 d-flex align-items-center text-white">
            <i class="fa fa-layer-group me-2"></i>
            <span>Bulk Assign Employee Commission</span>
        </h1>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body p-4 bg-white">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger p-2 mb-3">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <strong>Please correct the following errors:</strong>
                    <ul class="mb-0 ps-3 mt-1">
                        @foreach ($this->getErrorBag()->toArray() as $field => $errors)
                            <li>{{ ucfirst(str_replace('_', ' ', $field)) }}: {{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Step 1: Employee + Commission --}}
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-user-circle me-2 text-primary"></i>
                        Who &amp; How Much
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label for="bulk_employee_id" class="form-label mb-2 fw-medium d-flex align-items-center">
                                <i class="fa fa-user me-1 text-primary"></i>
                                <span>Employee</span> <span class="text-danger ms-1">*</span>
                            </label>
                            <div class="input-group shadow-sm" wire:ignore>
                                {{ html()->select('bulk_employee_id', [])->value('')->class('select-employee_id-list border-primary-subtle')->id('bulk_employee_id')->attribute('style', 'width:100%')->placeholder('Search and select employee...') }}
                            </div>
                            <div class="form-text ms-1 mt-2">
                                <i class="fa fa-info-circle me-1 text-primary"></i>
                                Commission is assigned to this employee for every matching product
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label for="bulk_commission_percentage" class="form-label mb-2 fw-medium d-flex align-items-center">
                                <i class="fa fa-percent me-1 text-warning"></i>
                                <span>Commission Percentage</span> <span class="text-danger ms-1">*</span>
                            </label>
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-warning text-white border-warning">
                                    <i class="fa fa-percent"></i>
                                </span>
                                <input type="number" wire:model="commission_percentage" id="bulk_commission_percentage"
                                    class="form-control border-warning-subtle shadow-sm" placeholder="0.00" step="0.01" min="0" max="100" required>
                                <span class="input-group-text bg-light border-warning-subtle">%</span>
                            </div>
                            <div class="form-text ms-1 mt-2">
                                <i class="fa fa-info-circle me-1 text-warning"></i>
                                Applied to all matching products
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Step 2: Scope filters --}}
            <div class="card shadow-sm mb-3 border-0">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-filter me-2 text-info"></i>
                        Select Scope
                    </h5>
                    <span class="text-muted small">Pick any combination — leave a filter empty to ignore it</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label mb-2 fw-medium d-flex align-items-center">
                                <i class="fa fa-building me-1 text-secondary"></i> Department
                            </label>
                            <div class="input-group shadow-sm" wire:ignore>
                                {{ html()->select('bulk_department_ids', [])->class('select-department_id-list border-secondary-subtle')->id('bulk_department_ids')->multiple()->attribute('style', 'width:100%')->placeholder('All departments') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2 fw-medium d-flex align-items-center">
                                <i class="fa fa-tags me-1 text-secondary"></i> Main Category
                            </label>
                            <div class="input-group shadow-sm" wire:ignore>
                                {{ html()->select('bulk_main_category_ids', [])->class('select-category_id-parent border-secondary-subtle')->id('bulk_main_category_ids')->multiple()->attribute('style', 'width:100%')->placeholder('All main categories') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2 fw-medium d-flex align-items-center">
                                <i class="fa fa-tag me-1 text-secondary"></i> Sub Category
                            </label>
                            <div class="input-group shadow-sm" wire:ignore>
                                {{ html()->select('bulk_sub_category_ids', [])->class('select-category_id-list border-secondary-subtle')->id('bulk_sub_category_ids')->multiple()->attribute('style', 'width:100%')->placeholder('All sub categories') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2 fw-medium d-flex align-items-center">
                                <i class="fa fa-copyright me-1 text-secondary"></i> Brand
                            </label>
                            <div class="input-group shadow-sm" wire:ignore>
                                {{ html()->select('bulk_brand_ids', [])->class('select-brand_id-list border-secondary-subtle')->id('bulk_brand_ids')->multiple()->attribute('style', 'width:100%')->placeholder('All brands') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Live preview --}}
            <div class="alert {{ $this->hasFilters ? 'alert-info' : 'alert-warning' }} d-flex align-items-center border-0 shadow-sm mb-3">
                <i class="fa {{ $this->hasFilters ? 'fa-search' : 'fa-exclamation-triangle' }} fs-4 me-3"></i>
                <div>
                    @if ($this->hasFilters)
                        This will apply to
                        <strong class="fs-5">{{ number_format($this->matchCount) }}</strong>
                        matching item{{ $this->matchCount == 1 ? '' : 's' }} (products &amp; services).
                    @else
                        <strong>No filters selected.</strong> This will apply to
                        <strong class="fs-5">ALL {{ number_format($this->matchCount) }}</strong>
                        products &amp; services in your catalogue.
                    @endif
                </div>
            </div>

            {{-- Overwrite option --}}
            <div class="form-check form-switch ms-1">
                <input class="form-check-input" type="checkbox" role="switch" id="bulk_overwrite" wire:model="overwrite">
                <label class="form-check-label" for="bulk_overwrite">
                    <span class="fw-medium">Overwrite existing commissions</span>
                    <span class="d-block text-muted small">
                        When on, products this employee already has a commission for are updated to the new percentage.
                        When off, existing entries are left untouched and skipped.
                    </span>
                </label>
            </div>
        </div>
        <div class="modal-footer bg-light">
            <div class="d-flex justify-content-between w-100">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>
                    Cancel
                </button>
                <button type="submit" class="btn btn-info text-white"
                    wire:confirm="Apply this commission to {{ $this->matchCount }} item(s)?">
                    <i class="fa fa-check-double me-1"></i>
                    Apply to {{ number_format($this->matchCount) }} item(s)
                </button>
            </div>
        </div>
    </form>
    @push('scripts')
        {{-- employeeSelect (.select-employee_id-list) is already included by the sibling
             individual-commission modal (page.blade) on this page; re-including it here would
             double-initialize TomSelect on the same elements. Only include the selects unique
             to the bulk modal. --}}
        @include('components.select.departmentSelect')
        @include('components.select.categorySelect')
        @include('components.select.brandSelect')
        <script>
            $(document).ready(function() {
                function syncMulti(id, prop) {
                    $('#' + id).on('change', function() {
                        @this.set(prop, $(this).val() || []);
                    });
                }
                syncMulti('bulk_department_ids', 'department_ids');
                syncMulti('bulk_main_category_ids', 'main_category_ids');
                syncMulti('bulk_sub_category_ids', 'sub_category_ids');
                syncMulti('bulk_brand_ids', 'brand_ids');

                $('#bulk_employee_id').on('change', function(e) {
                    @this.set('employee_id', e.target.value);
                });

                window.addEventListener('ResetBulkDropDownValues', event => {
                    ['bulk_employee_id', 'bulk_department_ids', 'bulk_main_category_ids',
                        'bulk_sub_category_ids', 'bulk_brand_ids'
                    ].forEach(function(id) {
                        var ts = document.querySelector('#' + id)?.tomselect;
                        if (ts) ts.clear();
                    });
                });
            });
        </script>
    @endpush
</div>
