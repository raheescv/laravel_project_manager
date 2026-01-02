<div>
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h5 class="mb-0">Tax Report</h5>
                    <small class="text-muted">Detailed tax credit and debit entries from journal entries</small>
                </div>
                <div>
                    @can('product.export')
                        <button class="btn btn-success btn-sm d-flex align-items-center" title="Export to Excel" data-bs-toggle="tooltip" wire:click="export">
                            <i class="demo-pli-file-excel me-md-1 fs-5"></i>
                            <span class="d-none d-md-inline">Export</span>
                        </button>
                    @endcan
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
                            <th class="border-bottom px-3 py-3">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="model" label="Transaction Type" />
                            </th>
                            <th class="border-bottom px-3 py-3">Reference</th>
                            <th class="border-bottom px-3 py-3">Description</th>
                            <th class="border-bottom px-3 py-3">Remarks</th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="debit" label="Debit (Tax Credit)" />
                            </th>
                            <th class="border-bottom px-3 py-3 text-end">
                                <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit" label="Credit (Tax Liability)" />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            <tr>
                                <td class="px-3 py-2">
                                    <span class="text-dark">{{ systemDate($entry->date) }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    @php
                                        $badgeClass = match($entry->model) {
                                            'Purchase' => 'bg-success',
                                            'PurchaseReturn' => 'bg-danger',
                                            'Sale' => 'bg-info',
                                            'SaleReturn' => 'bg-warning',
                                            default => 'bg-secondary'
                                        };
                                        $label = match($entry->model) {
                                            'Purchase' => 'Purchase',
                                            'PurchaseReturn' => 'Purchase Return',
                                            'Sale' => 'Sale',
                                            'SaleReturn' => 'Sale Return',
                                            default => $entry->model
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $label }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-muted">{{ $entry->reference_number ?? $entry->journal?->reference_number ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-dark">{{ $entry->description ?? $entry->journal?->description ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-muted small" title="{{ $entry->remarks ?? '-' }}">{{ \Illuminate\Support\Str::limit($entry->remarks ?? '-', 50) }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">
                                    @if($entry->debit > 0)
                                        <span class="text-success fw-bold">{{ currency($entry->debit) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-end">
                                    @if($entry->credit > 0)
                                        <span class="text-info fw-bold">{{ currency($entry->credit) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                    No tax entries found for the selected date range
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th class="px-3 py-3" colspan="4">Total</th>
                            <th class="px-3 py-3 text-muted small">{{ $totals->total_count ?? 0 }} entries</th>
                            <th class="px-3 py-3 text-end fw-bold text-success">{{ currency($totals->total_debit ?? 0) }}</th>
                            <th class="px-3 py-3 text-end fw-bold text-info">{{ currency($totals->total_credit ?? 0) }}</th>
                        </tr>
                        <tr class="bg-primary ">
                            <th class="px-3 py-3 text-white" colspan="5">Net Tax Payable (Liability - Credit)</th>
                            <th class="px-3 py-3 text-end fw-bold" colspan="2">
                                @php
                                    $netPayable = ($totals->total_credit ?? 0) - ($totals->total_debit ?? 0);
                                @endphp
                                @if($netPayable >= 0)
                                    <span class="text-white">{{ currency($netPayable) }}</span>
                                @else
                                    <span class="text-warning">{{ currency(abs($netPayable)) }} (Refund)</span>
                                @endif
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer bg-white">
                {{ $entries->links() }}
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
