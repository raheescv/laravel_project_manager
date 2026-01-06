<div>
    <div class="modal-header bg-primary bg-gradient text-white">
        <h1 class="modal-title fs-5 d-flex align-items-center text-white">
            <i class="fa fa-percent me-2"></i>
            <span>{{ isset($employee_commissions['id']) ? 'Edit Employee Commission' : 'Add New Employee Commission' }}</span>
            @if (isset($employee_commissions['id']))
                <span class="badge bg-light text-primary ms-2 fs-6 d-flex align-items-center">
                    <i class="fa fa-id-badge me-1"></i>ID: {{ $employee_commissions['id'] }}
                </span>
            @endif
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
                            <li>{{ str_replace('employee_commissions.', '', ucfirst(str_replace('_', ' ', $field))) }}: {{ $errors[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-light py-3">
                    <h5 class="card-title mb-0 fw-semibold d-flex align-items-center">
                        <i class="fa fa-link me-2 text-primary"></i>
                        Commission Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="employee_id" class="form-label mb-2 fw-medium d-flex align-items-center">
                                    <i class="fa fa-user me-1 text-primary"></i>
                                    <span>Employee</span> <span class="text-danger ms-1">*</span>
                                </label>
                                <div class="input-group shadow-sm" wire:ignore>
                                    {{ html()->select('employee_id', [])->value('')->class('select-employee_id-list border-primary-subtle')->id('modal_employee_id')->attribute('style', 'width:100%')->placeholder('Search and select employee...') }}
                                </div>
                                <div class="form-text ms-1 mt-2">
                                    <i class="fa fa-info-circle me-1 text-primary"></i>
                                    Search by employee name, mobile, or email
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="product_id" class="form-label mb-2 fw-medium d-flex align-items-center">
                                    <i class="fa fa-box me-1 text-success"></i>
                                    <span>Product</span> <span class="text-danger ms-1">*</span>
                                </label>
                                <div class="input-group shadow-sm" wire:ignore>
                                    {{ html()->select('product_id', [])->value('')->class('select-product_id-list border-success-subtle')->id('modal_product_id')->attribute('style', 'width:100%')->placeholder('Search and select product...') }}
                                </div>
                                <div class="form-text ms-1 mt-2">
                                    <i class="fa fa-info-circle me-1 text-success"></i>
                                    Search by product name, code, or barcode
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="commission_percentage" class="form-label mb-2 fw-medium d-flex align-items-center">
                                    <i class="fa fa-percent me-1 text-warning"></i>
                                    <span>Commission Percentage</span> <span class="text-danger ms-1">*</span>
                                </label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-warning text-white border-warning">
                                        <i class="fa fa-percent"></i>
                                    </span>
                                    <input type="number" wire:model="employee_commissions.commission_percentage" id="commission_percentage" class="form-control border-warning-subtle shadow-sm"
                                        placeholder="0.00" step="0.01" min="0" max="100" required>
                                    <span class="input-group-text bg-light border-warning-subtle">%</span>
                                </div>
                                <div class="form-text ms-1 mt-2">
                                    <i class="fa fa-info-circle me-1 text-warning"></i>
                                    Enter a value between 0 and 100 (e.g., 5.50 for 5.5%)
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (isset($employee_commissions['id']))
                <div class="alert alert-info p-3 mb-0 border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-info-circle me-2 fs-5"></i>
                        <div>
                            <strong>Note:</strong> This commission configuration is unique per employee and product combination.
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="modal-footer bg-light">
            <div class="d-flex justify-content-between w-100">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i>
                    Cancel
                </button>
                <div>
                    <button type="button" wire:click="save('completed')" class="btn btn-success">
                        <i class="fa fa-save me-1"></i>
                        Save & Add New
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check me-1"></i>
                        {{ isset($employee_commissions['id']) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
    @push('scripts')
        @include('components.select.employeeSelect')
        @include('components.select.productSelect')
        <script>
            $(document).ready(function() {
                // Sync employee_id with Livewire
                $('#modal_employee_id').on('change', function(e) {
                    @this.set('employee_commissions.employee_id', e.target.value);
                });

                // Sync product_id with Livewire
                $('#modal_product_id').on('change', function(e) {
                    @this.set('employee_commissions.product_id', e.target.value);
                });

                // Set initial values when editing
                window.addEventListener('ToggleEmployeeCommissionModal', event => {
                    setTimeout(function() {
                        var employeeId = @js($employee_commissions['employee_id'] ?? null);
                        var productId = @js($employee_commissions['product_id'] ?? null);

                        if (employeeId) {
                            var employeeTomSelect = document.querySelector('#modal_employee_id')?.tomselect;
                            if (employeeTomSelect) {
                                @php
                                    if (isset($employee_commissions['employee_id']) && $employee_commissions['employee_id']) {
                                        $employee = \App\Models\User::find($employee_commissions['employee_id']);
                                    }
                                @endphp
                                @if (isset($employee) && $employee)
                                    var employeeData = {
                                        id: {{ $employee->id }},
                                        name: @js($employee->name),
                                        mobile: @js($employee->mobile ?? ''),
                                        email: @js($employee->email ?? '')
                                    };
                                    employeeTomSelect.addOption(employeeData);
                                    employeeTomSelect.setValue({{ $employee->id }}, true);
                                @endif
                            }
                        }

                        if (productId) {
                            var productTomSelect = document.querySelector('#modal_product_id')?.tomselect;
                            if (productTomSelect) {
                                @php
                                    if (isset($employee_commissions['product_id']) && $employee_commissions['product_id']) {
                                        $product = \App\Models\Product::find($employee_commissions['product_id']);
                                    }
                                @endphp
                                @if (isset($product) && $product)
                                    var productData = {
                                        id: {{ $product->id }},
                                        name: @js($product->name),
                                        code: @js($product->code ?? ''),
                                        barcode: @js($product->barcode ?? ''),
                                        mrp: {{ $product->mrp ?? 0 }},
                                        cost: {{ $product->cost ?? 0 }},
                                        thumbnail: @js($product->thumbnail ?? '')
                                    };
                                    productTomSelect.addOption(productData);
                                    productTomSelect.setValue({{ $product->id }}, true);
                                @endif
                            }
                        }
                    }, 300);
                });
                window.addEventListener('ResetDropDownValues', event => {
                    var employeeTomSelect = document.querySelector('#modal_employee_id')?.tomselect;
                    var productTomSelect = document.querySelector('#modal_product_id')?.tomselect;
                    if (employeeTomSelect) employeeTomSelect.clear();
                    if (productTomSelect) productTomSelect.clear();
                });
                window.addEventListener('SelectDropDownValues', event => {
                    var data = event.detail[0];
                    if (data && data.product_id) {
                        @this.set('employee_commissions.product_id', data.product_id);
                        var productTomSelectInstance = document.querySelector('#modal_product_id').tomselect;
                        if (productTomSelectInstance && data.product) {
                            var preselectedData = {
                                id: data.product_id,
                                name: data.product['name'],
                            };
                            productTomSelectInstance.addOption(preselectedData);
                            productTomSelectInstance.addItem(preselectedData.id);
                        }
                    }
                    if (data && data.employee_id) {
                        @this.set('employee_commissions.employee_id', data.employee_id);
                        var employeeTomSelectInstance = document.querySelector('#modal_employee_id').tomselect;
                        if (employeeTomSelectInstance && data.employee) {
                            var preselectedData = {
                                id: data.employee_id,
                                name: data.employee['name'],
                            };
                            employeeTomSelectInstance.addOption(preselectedData);
                            employeeTomSelectInstance.addItem(preselectedData.id);
                        }
                    }
                });
            });
        </script>
    @endpush
</div>
