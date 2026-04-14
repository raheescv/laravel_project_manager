<div>
    {{-- Loading Bar --}}
    <div wire:loading.delay class="position-fixed top-0 start-0 w-100" style="z-index: 1060; height: 3px;">
        <div class="bg-primary h-100 bs-loading-bar"></div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4">
                    <label for="branch_id" class="form-label small text-muted mb-1">Branch</label>
                    <div wire:ignore>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-branch_id-list')->id('branch_id')->placeholder('All Branches') }}
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="period" class="form-label small text-muted mb-1">Period</label>
                    <select wire:model.live="period" class="form-select" id="period">
                        <option value="monthly">Current Month</option>
                        <option value="quarterly">Current Quarter</option>
                        <option value="yearly">Current Year</option>
                        <option value="previous_month">Previous Month</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4">
                    <label for="end_date" class="form-label small text-muted mb-1">As Of</label>
                    <input type="date" wire:model.live="end_date" class="form-control" id="end_date">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small text-muted mb-1">Display</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="hideCustomers" id="hideCustomers">
                            <label class="form-check-label small" for="hideCustomers">Hide Customers</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.live="hideVendors" id="hideVendors">
                            <label class="form-check-label small" for="hideVendors">Hide Vendors</label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 text-end">
                    <label class="form-label small text-muted mb-1">&nbsp;</label>
                    <div class="d-flex gap-2 justify-content-end">
                        <button wire:click="export" class="btn btn-sm btn-success">
                            <i class="pli-file-excel me-1"></i>Excel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Row --}}
    @php
        $bsDifference = abs($totalAssets - ($totalLiabilities + $totalEquity));
        $bsIsBalanced = $bsDifference < 0.01;
    @endphp
    <div class="row g-3 mb-4">
        @php
            $summaryCards = [
                ['label' => 'Total Assets', 'value' => abs($totalAssets), 'color' => 'primary', 'icon' => 'pli-money-bag'],
                ['label' => 'Total Liabilities', 'value' => abs($totalLiabilities), 'color' => 'warning', 'icon' => 'pli-credit-card'],
                ['label' => 'Total Equity', 'value' => abs($totalEquity), 'color' => 'success', 'icon' => 'pli-coins'],
            ];
        @endphp
        @foreach ($summaryCards as $card)
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3 bg-{{ $card['color'] }} bg-opacity-10" style="width: 48px; height: 48px; min-width: 48px;">
                            <i class="{{ $card['icon'] }} fs-4 text-{{ $card['color'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-muted">{{ $card['label'] }}</div>
                            <div class="fs-5 fw-bold">{{ currency($card['value']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Balance Equation Check --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                <strong>{{ currency(abs($totalAssets)) }}</strong> <span class="text-muted">(Assets)</span>
                = <strong>{{ currency(abs($totalLiabilities)) }}</strong> <span class="text-muted">(Liabilities)</span>
                + <strong>{{ currency(abs($totalEquity)) }}</strong> <span class="text-muted">(Equity)</span>
            </div>
            <span class="badge bg-{{ $bsIsBalanced ? 'success' : 'danger' }} bg-opacity-10 text-{{ $bsIsBalanced ? 'success' : 'danger' }} px-3 py-1">
                {{ $bsIsBalanced ? 'Balanced' : 'Unbalanced (' . currency($bsDifference) . ')' }}
            </span>
        </div>
    </div>

    @if (!$bsIsBalanced)
        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4 py-2" role="alert">
            <i class="pli-warning-triangle fs-4 text-danger me-3"></i>
            <div>
                <strong>Balance Sheet Mismatch</strong> &mdash; Difference of <strong>{{ currency($bsDifference) }}</strong> between Assets and Liabilities + Equity.
            </div>
        </div>
    @endif

    {{-- Balance Sheet Columns --}}
    <div class="row g-3">
        {{-- Assets Column --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3" style="border-bottom: 3px solid var(--bs-primary);">
                    <h6 class="mb-0 fw-bold text-primary">
                        <i class="pli-money-bag me-2"></i>Assets
                    </h6>
                </div>
                <div class="card-body py-2">
                    @if (count($currentAssets) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Current Assets</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $currentAssets, 'end_date' => $end_date, 'color' => 'primary'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Current Assets</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalCurrentAssets)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($fixedAssets) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Fixed Assets</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $fixedAssets, 'end_date' => $end_date, 'color' => 'primary'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Fixed Assets</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalFixedAssets)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($otherAssets) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Other Assets</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $otherAssets, 'end_date' => $end_date, 'color' => 'primary'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Other Assets</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalOtherAssets)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($currentAssets) === 0 && count($fixedAssets) === 0 && count($otherAssets) === 0)
                        <div class="text-center text-muted py-4 small fst-italic">No asset accounts found</div>
                    @endif
                </div>
                <div class="card-footer bg-primary bg-opacity-10 border-0 py-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Assets</span>
                        <span class="text-nowrap">{{ currency(abs($totalAssets)) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Liabilities Column --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3" style="border-bottom: 3px solid var(--bs-warning);">
                    <h6 class="mb-0 fw-bold text-warning">
                        <i class="pli-credit-card me-2"></i>Liabilities
                    </h6>
                </div>
                <div class="card-body py-2">
                    @if (count($currentLiabilities) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Current Liabilities</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $currentLiabilities, 'end_date' => $end_date, 'color' => 'warning'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Current Liabilities</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalCurrentLiabilities)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($longTermLiabilities) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Long Term Liabilities</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $longTermLiabilities, 'end_date' => $end_date, 'color' => 'warning'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Long Term Liabilities</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalLongTermLiabilities)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($currentLiabilities) === 0 && count($longTermLiabilities) === 0)
                        <div class="text-center text-muted py-4 small fst-italic">No liability accounts found</div>
                    @endif
                </div>
                <div class="card-footer bg-warning bg-opacity-10 border-0 py-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Liabilities</span>
                        <span class="text-nowrap">{{ currency(abs($totalLiabilities)) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Equity Column --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3" style="border-bottom: 3px solid var(--bs-success);">
                    <h6 class="mb-0 fw-bold text-success">
                        <i class="pli-coins me-2"></i>Equity
                    </h6>
                </div>
                <div class="card-body py-2">
                    @if (count($ownerEquity) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Owner's Equity</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $ownerEquity, 'end_date' => $end_date, 'color' => 'success'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Owner's Equity</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalEquityAccounts)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($retainedEarningAccounts) > 0)
                        <div class="mb-3">
                            <div class="fw-bold small text-uppercase text-muted border-bottom pb-1 mb-2">Retained Earnings</div>
                            @include('livewire.reports.partials.balance-sheet-section', ['categories' => $retainedEarningAccounts, 'end_date' => $end_date, 'color' => 'success'])
                            <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                <strong class="small">Total Retained Earnings</strong>
                                <strong class="small text-nowrap">{{ currency(abs($totalRetainedEarnings)) }}</strong>
                            </div>
                        </div>
                    @endif

                    @if (count($ownerEquity) === 0 && count($retainedEarningAccounts) === 0)
                        <div class="text-center text-muted py-4 small fst-italic">No equity accounts found</div>
                    @endif
                </div>
                <div class="card-footer bg-success bg-opacity-10 border-0 py-2">
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total Equity</span>
                        <span class="text-nowrap">{{ currency(abs($totalEquity)) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    @this.set('branch_id', $(this).val() || null);
                });
            });
        </script>
    @endpush

    <style>
        .bs-loading-bar {
            animation: bs-loading 1.5s ease-in-out infinite;
        }
        @keyframes bs-loading {
            0% { width: 0; margin-left: 0; }
            50% { width: 60%; margin-left: 20%; }
            100% { width: 0; margin-left: 100%; }
        }
        @media print {
            .card-header .btn { display: none !important; }
        }
    </style>
</div>
