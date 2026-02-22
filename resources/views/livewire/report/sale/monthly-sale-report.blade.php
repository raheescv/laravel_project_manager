<div class="monthly-sale-report-ui">
    @php
        $periodLabel = (\DateTime::createFromFormat('!m', $from_month)?->format('M') ?? $from_month) . ' ' . $from_year . ' - ' . (\DateTime::createFromFormat('!m', $to_month)?->format('M') ?? $to_month) . ' ' . $to_year;
        $monthsCount = count($data);
        $avgMonthlySale = $monthsCount > 0 ? (($total['net_sale'] ?? 0) / $monthsCount) : 0;
        $collectionRate = ($total['net_sale'] ?? 0) > 0 ? ((($total['paid_total'] ?? 0) / ($total['net_sale'] ?? 1)) * 100) : 0;
    @endphp

    <div class="card shadow-sm border-0 monthly-top-panel">
        <div class="card-header py-4 border-0">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge monthly-chip-primary px-3 py-2">Sales + Tailoring</span>
                        <span class="badge monthly-chip-secondary px-3 py-2">{{ $periodLabel }}</span>
                    </div>
                    <h5 class="mb-1 fw-semibold">Monthly Sale Report</h5>
                    <small class="text-muted">Clean monthly performance view with payment split and collection visibility</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 quick-range-btn quick-range-btn-active" data-range="this_month">This Month</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 quick-range-btn" data-range="last_3_months">Last 3 Months</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 quick-range-btn" data-range="this_year">This Year</button>
                </div>
            </div>

            <div class="row g-2 g-md-3 align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-2 rounded-3 border bg-light h-100 monthly-filter-box">
                        <label class="form-label small fw-semibold text-secondary mb-1">From Year</label>
                        <div class="input-group input-group-sm monthly-input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-calendar text-success"></i></span>
                            {{ html()->select('from_year', array_combine(range(date('Y'), date('Y') - 10), range(date('Y'), date('Y') - 10)))->value($from_year)->class('form-control border-start-0 ps-0')->id('from_year')->attribute('wire:model.live', 'from_year') }}
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-2 rounded-3 border bg-light h-100 monthly-filter-box">
                        <label class="form-label small fw-semibold text-secondary mb-1">From Month</label>
                        <div class="input-group input-group-sm monthly-input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-calendar text-success"></i></span>
                            {{ html()->select('from_month', $months)->value($from_month)->class('form-control border-start-0 ps-0')->id('from_month')->attribute('wire:model.live', 'from_month') }}
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-2 rounded-3 border bg-light h-100 monthly-filter-box">
                        <label class="form-label small fw-semibold text-secondary mb-1">To Year</label>
                        <div class="input-group input-group-sm monthly-input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-calendar text-success"></i></span>
                            {{ html()->select('to_year', array_combine(range(date('Y'), date('Y') - 10), range(date('Y'), date('Y') - 10)))->value($to_year)->class('form-control border-start-0 ps-0')->id('to_year')->attribute('wire:model.live', 'to_year') }}
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <div class="p-2 rounded-3 border bg-light h-100 monthly-filter-box">
                        <label class="form-label small fw-semibold text-secondary mb-1">To Month</label>
                        <div class="input-group input-group-sm monthly-input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-calendar text-success"></i></span>
                            {{ html()->select('to_month', $months)->value($to_month)->class('form-control border-start-0 ps-0')->id('to_month')->attribute('wire:model.live', 'to_month') }}
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-5 col-xl-3" wire:ignore>
                    <div class="p-2 rounded-3 border bg-light h-100 monthly-filter-box">
                        <label class="form-label small fw-semibold text-secondary mb-1">Branch</label>
                        <div class="input-group input-group-sm monthly-input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa fa-building text-success"></i></span>
                            {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:100%')->placeholder('Select Branch') }}
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-1">
                    @can('product.export')
                        <button class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2 monthly-export-btn" title="Export to Excel" data-bs-toggle="tooltip" wire:click="exportExcel">
                            <i class="demo-pli-file-excel fs-5"></i>
                            <span>Excel</span>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 monthly-kpi-card bg-primary-subtle">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">Net Sale</small>
                    <div class="h4 mb-0 text-primary-emphasis fw-semibold">{{ currency($total['net_sale'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 monthly-kpi-card bg-success-subtle">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">Paid (Total)</small>
                    <div class="h4 mb-0 text-success-emphasis fw-semibold">{{ currency($total['paid_total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 monthly-kpi-card bg-warning-subtle">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">Credit Exposure</small>
                    <div class="h4 mb-0 text-warning-emphasis fw-semibold">{{ currency($total['credit'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 monthly-kpi-card bg-info-subtle">
                <div class="card-body">
                    <small class="text-muted d-block mb-1">Collection Rate</small>
                    <div class="h4 mb-0 text-info-emphasis fw-semibold">{{ number_format($collectionRate, 1) }}%</div>
                    <small class="text-muted">Avg monthly sale: {{ currency($avgMonthlySale) }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3 border-0 shadow-sm monthly-table-card">
        <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 monthly-table-title">
            <h6 class="mb-0">Monthly Breakdown</h6>
            <small class="text-muted">{{ $monthsCount }} month(s)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive rounded-3 border shadow-sm bg-white">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="table-light text-nowrap">
                        <tr class="text-capitalize">
                            <th class="border-bottom px-3 py-3">Month</th>
                            <th class="border-bottom px-3 py-3 text-end">Gross Sales</th>
                            <th class="border-bottom px-3 py-3 text-end">Discount</th>
                            <th class="border-bottom px-3 py-3 text-end">Net Sale</th>
                            <th class="border-bottom px-3 py-3 text-end">Paid (Total)</th>
                            <th class="border-bottom px-3 py-3 text-end">Credit</th>
                            <th class="border-bottom px-3 py-3 text-end">Card</th>
                            <th class="border-bottom px-3 py-3 text-end">Cash</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td class="px-3 py-2"><span class="text-dark">{{ $item['month_name'] }}</span></td>
                                <td class="px-3 py-2 text-end"><span class="fw-medium">{{ currency($item['gross_sales']) }}</span></td>
                                <td class="px-3 py-2 text-end text-danger">{{ currency($item['discount']) }}</td>
                                <td class="px-3 py-2 text-end"><span class="fw-medium text-primary">{{ currency($item['net_sale']) }}</span></td>
                                <td class="px-3 py-2 text-end"><span class="fw-medium text-success">{{ currency($item['paid_total']) }}</span></td>
                                <td class="px-3 py-2 text-end"><span class="fw-medium text-warning">{{ currency($item['credit']) }}</span></td>
                                <td class="px-3 py-2 text-end"><span class="text-info">{{ currency($item['card']) }}</span></td>
                                <td class="px-3 py-2 text-end"><span class="text-success">{{ currency($item['cash']) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                    No data available for the selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-group-divider bg-light">
                        <tr>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-3 py-3 text-end fw-semibold">{{ currency($total['gross_sales'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end text-danger fw-semibold">{{ currency($total['discount'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-primary">{{ currency($total['net_sale'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-success">{{ currency($total['paid_total'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-warning">{{ currency($total['credit'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-info">{{ currency($total['card'] ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-semibold text-success">{{ currency($total['cash'] ?? 0) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function() {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });

                $(document).on('click', '.quick-range-btn', function() {
                    $('.quick-range-btn').removeClass('quick-range-btn-active');
                    $(this).addClass('quick-range-btn-active');

                    const range = $(this).data('range');
                    const now = new Date();
                    const currentYear = now.getFullYear();
                    const currentMonth = String(now.getMonth() + 1).padStart(2, '0');

                    if (range === 'this_month') {
                        @this.set('from_year', String(currentYear));
                        @this.set('to_year', String(currentYear));
                        @this.set('from_month', currentMonth);
                        @this.set('to_month', currentMonth);
                        return;
                    }

                    if (range === 'last_3_months') {
                        const start = new Date(currentYear, now.getMonth() - 2, 1);
                        const startYear = String(start.getFullYear());
                        const startMonth = String(start.getMonth() + 1).padStart(2, '0');
                        @this.set('from_year', startYear);
                        @this.set('to_year', String(currentYear));
                        @this.set('from_month', startMonth);
                        @this.set('to_month', currentMonth);
                        return;
                    }

                    if (range === 'this_year') {
                        @this.set('from_year', String(currentYear));
                        @this.set('to_year', String(currentYear));
                        @this.set('from_month', '01');
                        @this.set('to_month', '12');
                    }
                });
            });
        </script>
    @endpush

    @push('styles')
        <style>
            .monthly-sale-report-ui .monthly-top-panel .card-header {
                background: #f8fafc;
                border-radius: 14px;
                border: 1px solid #e2e8f0;
            }

            .monthly-sale-report-ui .monthly-chip-primary {
                background: #dbe4f3;
                color: #2d4a83;
                font-weight: 600;
            }

            .monthly-sale-report-ui .monthly-chip-secondary {
                background: #e8edf5;
                color: #6b7280;
                font-weight: 600;
            }

            .monthly-sale-report-ui .quick-range-btn {
                min-width: 120px;
                color: #565f6c;
            }

            .monthly-sale-report-ui .quick-range-btn.quick-range-btn-active {
                background: #ffffff;
                box-shadow: 0 0 0 1px #d1d8e4 inset;
                color: #49515d;
            }

            .monthly-sale-report-ui .monthly-input-group .input-group-text {
                min-width: 36px;
                min-height: 34px;
                justify-content: center;
                align-items: center;
                display: inline-flex;
                background: #e2e8f0 !important;
                color: #4b5563;
                border-color: #d4dbe5;
            }

            .monthly-sale-report-ui .monthly-input-group {
                flex-wrap: nowrap !important;
                align-items: stretch;
            }

            .monthly-sale-report-ui .monthly-filter-box {
                border-color: #d9e0ea !important;
                background: #f5f7fb !important;
            }

            .monthly-sale-report-ui .monthly-input-group .form-control,
            .monthly-sale-report-ui .monthly-input-group .select-assigned-branch_id-list {
                border-color: #d4dbe5 !important;
                min-height: 34px;
                font-size: 0.92rem;
            }

            .monthly-sale-report-ui .monthly-export-btn {
                min-height: 36px;
                border-radius: 10px;
                background: #56a800;
                border-color: #56a800;
                box-shadow: 0 3px 8px rgba(86, 168, 0, 0.2);
            }

            .monthly-sale-report-ui .monthly-input-group .ts-wrapper {
                flex: 1 1 auto;
                width: auto !important;
                min-height: 34px;
                margin: 0;
                display: flex;
                align-items: stretch;
            }

            .monthly-sale-report-ui .monthly-input-group .ts-wrapper.single .ts-control {
                width: 100%;
                min-height: 34px;
                border-color: #d4dbe5;
                font-size: 0.92rem;
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                padding-top: 0.25rem;
                padding-bottom: 0.25rem;
                display: flex;
                align-items: center;
            }

            .monthly-sale-report-ui .monthly-input-group .ts-wrapper.single .ts-control input {
                min-width: 0;
            }

            .monthly-sale-report-ui .monthly-kpi-card {
                border: 1px solid #dde3ed !important;
                border-radius: 12px;
            }

            .monthly-sale-report-ui .monthly-table-card {
                border-radius: 12px;
                overflow: hidden;
            }

            .monthly-sale-report-ui .monthly-table-title {
                padding-top: 14px;
                padding-bottom: 14px;
            }
        </style>
    @endpush
</div>
