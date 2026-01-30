<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Monthly Sale Report</h5>
                    <small class="text-muted">Monthly sales breakdown with payment methods</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-1">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->select('from_year', array_combine(range(date('Y'), date('Y') - 10), range(date('Y'), date('Y') - 10)))->value($from_year)->class('form-control border-start-0 ps-0')->id('from_year')->attribute('wire:model.live', 'from_year') }}
                    </div>
                    <label class="form-label small text-muted mt-1">From Year</label>
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->select('from_month', $months)->value($from_month)->class('form-control border-start-0 ps-0')->id('from_month')->attribute('wire:model.live', 'from_month') }}
                    </div>
                    <label class="form-label small text-muted mt-1">From Month</label>
                </div>
                <div class="col-md-1">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->select('to_year', array_combine(range(date('Y'), date('Y') - 10), range(date('Y'), date('Y') - 10)))->value($to_year)->class('form-control border-start-0 ps-0')->id('to_year')->attribute('wire:model.live', 'to_year') }}
                    </div>
                    <label class="form-label small text-muted mt-1">To Year</label>
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->select('to_month', $months)->value($to_month)->class('form-control border-start-0 ps-0')->id('to_month')->attribute('wire:model.live', 'to_month') }}
                    </div>
                    <label class="form-label small text-muted mt-1">To Month</label>
                </div>
                <div class="col-md-4" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-building text-success"></i>
                        </span>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:80%')->placeholder('Select Branch') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Branch</label>
                </div>
                <div class="col-md-1">
                    <div class="btn-group">
                        @can('product.export')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="exportExcel">
                                <i class="demo-pli-file-excel me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Excel</span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
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
                                <td class="px-3 py-2">
                                    <span class="text-dark fw-medium">{{ $item['month_name'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium">{{ currency($item['gross_sales']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end text-danger">
                                    {{ currency($item['discount']) }}
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium text-primary">{{ currency($item['net_sale']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium text-success">{{ currency($item['paid_total']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium text-warning">{{ currency($item['credit']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-info">{{ currency($item['card']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-success">{{ currency($item['cash']) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                    No data available for the selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th class="px-3 py-3">Total</th>
                            <th class="px-3 py-3 text-end fw-bold">{{ currency($total['gross_sales']) }}</th>
                            <th class="px-3 py-3 text-end text-danger fw-bold">{{ currency($total['discount']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-primary">{{ currency($total['net_sale']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-success">{{ currency($total['paid_total']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-warning">{{ currency($total['credit']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-info">{{ currency($total['card']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-success">{{ currency($total['cash']) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#branch_id').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('branch_id', value);
                });
            });
        </script>
    @endpush
</div>
