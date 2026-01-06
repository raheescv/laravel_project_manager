<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center mb-4">
                <div>
                    <h5 class="mb-0">Tax Report</h5>
                    <small class="text-muted">Tax credit and liability breakdown from Purchase, Purchase Return, Sale, and Sale Return</small>
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
                <div class="col-md-3" wire:ignore>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-building text-success"></i>
                        </span>
                        {{ html()->select('branch_id', [session('branch_id') => session('branch_name')])->value(session('branch_id'))->class('select-assigned-branch_id-list border-start-0 ps-0')->id('branch_id')->attribute('style', 'width:80%')->placeholder('Select Branch') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Branch</label>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa fa-filter text-success"></i>
                        </span>
                        {{ html()->select('transaction_type', ['all' => 'All Transactions', 'purchase' => 'Purchase Only', 'sale' => 'Sale Only'])->value('all')->class('form-control border-start-0 ps-0')->id('transaction_type')->attribute('wire:model.live', 'transaction_type') }}
                    </div>
                    <label class="form-label small text-muted mt-1">Transaction Type</label>
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
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="purchase_tax_credit" label="Purchase Tax Credit" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="purchase_return_tax_credit" label="Purchase Return Tax Credit" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_tax_credit" label="Net Tax Credit" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sale_tax_amount" label="Sale Tax Amount" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sale_return_tax_amount" label="Sale Return Tax Amount" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_tax_liability" label="Net Tax Liability" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="net_tax_payable" label="Net Tax Payable" />
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
                                    <span class="text-success fw-medium">{{ currency($item['purchase_tax_credit']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-danger fw-medium">{{ currency($item['purchase_return_tax_credit']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-success fw-bold">{{ currency($item['net_tax_credit']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-info fw-medium">{{ currency($item['sale_tax_amount']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-warning fw-medium">{{ currency($item['sale_return_tax_amount']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    <span class="text-info fw-bold">{{ currency($item['net_tax_liability']) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    @if($item['net_tax_payable'] >= 0)
                                        <span class="text-danger fw-bold">{{ currency($item['net_tax_payable']) }}</span>
                                    @else
                                        <span class="text-success fw-bold">{{ currency(abs($item['net_tax_payable'])) }} (Refund)</span>
                                    @endif
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
                            <th class="px-3 py-3 text-end fw-bold text-success">{{ currency($total['purchase_tax_credit']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-danger">{{ currency($total['purchase_return_tax_credit']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-success">{{ currency($total['net_tax_credit']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-info">{{ currency($total['sale_tax_amount']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-warning">{{ currency($total['sale_return_tax_amount']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-info">{{ currency($total['net_tax_liability']) }}</th>
                            <th class="px-3 py-3 text-end fw-bold">
                                @if($total['net_tax_payable'] >= 0)
                                    <span class="text-danger">{{ currency($total['net_tax_payable']) }}</span>
                                @else
                                    <span class="text-success">{{ currency(abs($total['net_tax_payable'])) }} (Refund)</span>
                                @endif
                            </th>
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


