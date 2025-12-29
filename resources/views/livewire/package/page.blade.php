<div>
    <style>
        .package-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }

        .content-section {
            padding: 1.5rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-in_progress {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .balance-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .section-title {
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* Package Details Styling */
        .form-group-enhanced {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group-enhanced label {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-group-enhanced label i {
            margin-right: 0.5rem;
            color: #667eea;
            font-size: 1rem;
        }

        .form-group-enhanced .form-control,
        .form-group-enhanced select {
            border: 1.5px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            background-color: #fff;
        }

        .form-group-enhanced .form-control:focus,
        .form-group-enhanced select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-group-enhanced .form-control:hover,
        .form-group-enhanced select:hover {
            border-color: #9ca3af;
        }

        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .summary-card table {
            margin-bottom: 0;
        }

        .summary-card table th {
            background-color: transparent;
            border: none;
            padding: 0.75rem 1rem;
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }

        .summary-card table td {
            border: none;
            padding: 0.75rem 1rem;
            text-align: right;
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
        }

        .summary-card table tr:first-child th {
            color: #667eea;
        }

        .summary-card table tr:first-child td {
            color: #667eea;
            font-size: 1.3rem;
        }

        .summary-card table tr:last-child th {
            color: #059669;
        }

        .summary-card table tr:last-child td {
            color: #059669;
            font-size: 1.2rem;
        }

        .form-section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #e5e7eb, transparent);
            margin: 2rem 0;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 10;
        }

        .input-icon-wrapper .form-control {
            padding-left: 2.5rem;
        }

        .textarea-wrapper {
            position: relative;
        }

        .textarea-wrapper textarea {
            min-height: 100px;
            resize: vertical;
        }
    </style>

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
            <div class="row g-3">
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

            <div class="form-section-divider"></div>

            <!-- Status, Amount and Summary Row -->
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="row g-3">
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
                                <textarea wire:model="packages.remarks" class="form-control" id="remarks" rows="3" placeholder="Enter any additional notes or remarks..."></textarea>
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
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="demo-psi-save fs-5 me-2"></i>
                                Save Package
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Package Items Section -->
    @if ($table_id)
        <div class="row">
            <div class="col-7">
                <div class="content-section">
                    <h4 class="section-title">
                        <i class="demo-psi-calendar-4 me-2"></i>Package Items
                    </h4>
                    @livewire('package.items', ['package_id' => $table_id])
                </div>
            </div>

            <!-- Payments Section -->
            <div class="col-5">
                <div class="content-section">
                    <h4 class="section-title">
                        <i class="demo-psi-wallet me-2"></i>Payments
                    </h4>
                    @livewire('package.payments', ['package_id' => $table_id])
                </div>
            </div>
        </div>
    @endif

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
        </script>
    @endpush
</div>
