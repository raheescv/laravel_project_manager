<div>

    @if ($this->getErrorBag()->count())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($this->getErrorBag()->toArray() as $key => $errors)
                    @foreach ($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Package Details Section -->
    <form wire:submit="save">
        <div class="content-section">
            <div class="d-flex justify-content-between align-items-center section-title mb-4" style="border-bottom: 2px solid #667eea; padding-bottom: 0.5rem;">
                <h4 class="mb-0">
                    <i class="demo-psi-file-edit me-2"></i>Package Details
                </h4>
                <div class="text-end">
                    <h2 class="mb-2">
                        @if ($table_id)
                            #{{ $table_id }}
                            @if ($table_id && $package)
                                <p class="mb-0">
                                    <span class="status-badge status-{{ $package->status }}">
                                        {{ ucWords(str_replace('_', ' ', $package->status)) }}
                                    </span>
                                </p>
                            @endif
                        @endif
                    </h2>
                </div>
            </div>

            <!-- Basic Information Row -->
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group-enhanced" wire:ignore>
                        <label class="form-label">
                            <i class="demo-psi-tag"></i>
                            Package Category <span class="text-danger">*</span>
                        </label>
                        {{ html()->select('package_category_id', $packageCategories)->class('tomSelect')->id('package_category_id')->attribute('wire:model.live', 'packages.package_category_id')->required(true)->attribute('style', 'width:100%')->placeholder('Select Package Category') }}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group-enhanced" wire:ignore>
                        <label class="form-label">
                            <i class="demo-psi-user"></i>
                            Account <span class="text-danger">*</span>
                        </label>
                        <select wire:model="packages.account_id" class="select-account_id" id="account_id" style="width:100%">
                            <option value="">Select Account</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group-enhanced">
                        <label class="form-label">
                            <i class="demo-psi-calendar-4"></i>
                            Start Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="demo-psi-calendar-4"></i>
                            <input type="date" wire:model="packages.start_date" class="form-control" required id="start_date">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group-enhanced">
                        <label class="form-label">
                            <i class="demo-psi-calendar-4"></i>
                            End Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="demo-psi-calendar-4"></i>
                            <input type="date" wire:model="packages.end_date" class="form-control" required id="end_date">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group-enhanced">
                                <label class="form-label">
                                    <i class="demo-psi-flag-2"></i>
                                    Status <span class="text-danger">*</span>
                                </label>
                                {{ html()->select('status', packageStatuses())->class('form-control')->id('status')->attribute('wire:model', 'packages.status')->required(true)->attribute('style', 'width:100%')->placeholder('Select Status') }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group-enhanced">
                                <label class="form-label">
                                    <i class="demo-psi-money"></i>
                                    Amount <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon-wrapper">
                                    <i class="demo-psi-money"></i>
                                    <input type="number" wire:model.live="packages.amount" class="form-control" required id="amount" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group-enhanced textarea-wrapper">
                                <label class="form-label">
                                    <i class="demo-psi-notepad"></i>
                                    Remarks
                                </label>
                                <textarea wire:model="packages.remarks" class="form-control" id="remarks" rows="8" placeholder="Enter any additional notes or remarks..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="summary-card">
                        <h6 class="mb-3 fw-bold text-center" style="color: #667eea;">
                            <i class="demo-psi-calculator me-2"></i>Financial Summary
                        </h6>
                        <table class="table table-bordered">
                            <tr>
                                <th><i class="demo-psi-money me-1"></i>Total</th>
                                <td>{{ currency($packages['amount'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <th><i class="demo-psi-wallet me-1"></i>Paid</th>
                                <td>{{ currency($packages['paid'] ?? 0) }}</td>
                            </tr>
                            <tr>
                                <th><i class="demo-psi-calculator me-1"></i>Balance</th>
                                <td>{{ currency($packages['balance'] ?? 0) }}</td>
                            </tr>
                        </table>
                        <div class="d-flex flex-column gap-2 mt-3">
                            @if ($table_id)
                                <a href="{{ route('package::statement', $table_id) }}" target="_blank" class="btn btn-info btn-sm w-100 d-flex align-items-center justify-content-center"
                                    style="font-weight: 500;">
                                    <i class="demo-psi-file-edit me-2"></i>Get Statement
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center" style="font-weight: 600; padding: 0.65rem 1rem;">
                                <i class="demo-psi-save me-2"></i>
                                Save Package
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @push('scripts')
        <script>
            window.addEventListener('SelectDropDownValues', event => {
                var data = event.detail[0];
                if (data && data.account_id) {
                    var accountTomSelectInstance = document.querySelector('#account_id').tomselect;
                    if (accountTomSelectInstance && data.account_id) {
                        var preselectedData = {
                            id: data.account_id,
                            name: data.account['name'],
                        };
                        accountTomSelectInstance.addOption(preselectedData);
                        accountTomSelectInstance.addItem(preselectedData.id);
                    }
                }
            });

            // Listen for package category changes to auto-fill amount and end_date
            document.addEventListener('DOMContentLoaded', function() {
                var packageCategorySelect = document.querySelector('#package_category_id');
                if (packageCategorySelect) {
                    // Wait for tomSelect to initialize
                    setTimeout(function() {
                        var tomSelectInstance = packageCategorySelect.tomselect;
                        if (tomSelectInstance) {
                            tomSelectInstance.on('change', function(value) {
                                // Update Livewire model which will trigger updatedPackagesPackageCategoryId
                                @this.set('packages.package_category_id', value, false);
                            });
                        }
                    }, 500);
                }
            });
        </script>
    @endpush
</div>
