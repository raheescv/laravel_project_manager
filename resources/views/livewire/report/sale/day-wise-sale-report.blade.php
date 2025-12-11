<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Day Wise Sale Report</h5>
                    <small class="text-muted">Daily sales breakdown with detailed metrics</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->date('from_date')->value('')->class('form-control border-start-0 ps-0')->id('from_date')->attribute('wire:model.live', 'from_date') }}
                    </div>
                    <label class="form-label small text-muted mt-1">From Date</label>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-calendar text-success"></i>
                        </span>
                        {{ html()->date('to_date')->value('')->class('form-control border-start-0 ps-0')->id('to_date')->attribute('wire:model.live', 'to_date') }}
                    </div>
                    <label class="form-label small text-muted mt-1">To Date</label>
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
                <div class="col-md-2">
                    <div class="input-group">
                        @can('product.export')
                            <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export">
                                <i class="demo-pli-file-excel me-md-1 fs-5"></i>
                                <span class="d-none d-md-inline">Export</span>
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
                            <th class="border-bottom px-3 py-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="date" label="Date" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="count" label="Count" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="quantity" label="Quantity" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_sale" label="Net Sale" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="gross_sale" label="Gross Sale" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="tax_amount" label="Tax Amount" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="discount" label="Discount" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="return_amount" label="Return Amount" />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td class="px-3 py-2">
                                    <span class="text-dark">{{ systemDate($item['date']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="badge bg-light text-dark">{{ $item['count'] }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium">{{ number_format($item['quantity'], 3) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium">{{ currency($item['net_sale']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="fw-medium">{{ currency($item['gross_sale']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-info">{{ currency($item['tax_amount']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end text-danger">
                                    {{ currency($item['discount']) }}
                                </td>
                                <td class="px-3 py-2 text-end text-warning">
                                    {{ currency($item['return_amount']) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                    No data available for the selected date range
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th class="px-3 py-3 text-end" colspan="1">Total</th>
                            <th class="px-3 py-3 text-end">
                                <span class="badge bg-primary">{{ $total['count'] }}</span>
                            </th>
                            <th class="px-3 py-3 text-end fw-bold">{{ number_format($total['quantity'], 3) }}</th>
                            <th class="px-3 py-3 text-end fw-bold">{{ currency($total['net_sale']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold">{{ currency($total['gross_sale']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-info">{{ currency($total['tax_amount']) }}</th>
                            <th class="px-3 py-3 text-end text-danger fw-bold">{{ currency($total['discount']) }}</th>
                            <th class="px-3 py-3 text-end text-warning fw-bold">{{ currency($total['return_amount']) }}</th>
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
