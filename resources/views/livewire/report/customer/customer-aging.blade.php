<div>
    <div class="card-header bg-white">
        <div class="row g-3">
            <div class="col-md-4 d-flex align-items-center">
                <div class="btn-group">
                    @can('report.customer aging')
                        <button class="btn btn-sm btn-outline-primary" title="Export as Excel" wire:click="export()">
                            <i class="demo-pli-file-excel me-1"></i> Export
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <div class="card-body px-0 pb-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0 border-bottom">
                <thead class="bg-light text-nowrap">
                    <tr>
                        <th class="ps-3">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="accounts.name" label="Customer Name" />
                        </th>
                        <th>
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="customer_mobile" label="Mobile" />
                        </th>
                        <th class="text-center">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="credit_period_days" label="Credit Period" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.invoice_no" label="Invoice No" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.date" label="Invoice Date" />
                        </th>
                        <th class="text-nowrap">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="due_date" label="Due Date" />
                        </th>
                        <th class="text-center">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="days_overdue" label="Days Overdue" />
                        </th>
                        <th class="text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.grand_total" label="Invoice Amount" />
                        </th>
                        <th class="text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.paid" label="Amount Paid" />
                        </th>
                        <th class="text-end">
                            <x-sortable-header :direction="$sortDirection" :sortField="$sortField" field="sales.balance" label="Outstanding" />
                        </th>
                        <th class="text-end">0-30 Days</th>
                        <th class="text-end">31-60 Days</th>
                        <th class="text-end">61-90 Days</th>
                        <th class="text-end">90+ Days</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-building fs-5 text-warning"></i>
                                    <span>{{ $sale->customer_name }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-pli-phone fs-5 text-info"></i>
                                    <span>{{ $sale->customer_mobile }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    {{ $sale->credit_period_days ? $sale->credit_period_days . ' days' : 'N/A' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('sale::view', $sale->id) }}" class="text-primary fw-semibold text-decoration-none" target="_blank">
                                    {{ $sale->invoice_no }}
                                </a>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-primary"></i>
                                    <span>{{ systemDate($sale->invoice_date) }}</span>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="demo-psi-calendar-4 fs-5 text-info"></i>
                                    <span>{{ systemDate($sale->due_date) }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if ($sale->days_overdue > 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger">
                                        {{ $sale->days_overdue }} days
                                    </span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success">Current</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-end fw-medium">{{ currency($sale->invoice_amount) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-success fw-semibold">{{ currency($sale->amount_paid) }}</div>
                            </td>
                            <td>
                                <div class="text-end text-danger fw-bold">{{ currency($sale->outstanding_balance) }}</div>
                            </td>
                            <td>
                                @if ($sale->aging_0_30 > 0)
                                    <div class="text-end">
                                        <span class="badge bg-info bg-opacity-10 text-info fw-semibold">{{ currency($sale->aging_0_30) }}</span>
                                    </div>
                                @else
                                    <div class="text-end text-muted">-</div>
                                @endif
                            </td>
                            <td>
                                @if ($sale->aging_31_60 > 0)
                                    <div class="text-end">
                                        <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold">{{ currency($sale->aging_31_60) }}</span>
                                    </div>
                                @else
                                    <div class="text-end text-muted">-</div>
                                @endif
                            </td>
                            <td>
                                @if ($sale->aging_61_90 > 0)
                                    <div class="text-end">
                                        <span class="badge bg-orange bg-opacity-10 text-orange fw-semibold">{{ currency($sale->aging_61_90) }}</span>
                                    </div>
                                @else
                                    <div class="text-end text-muted">-</div>
                                @endif
                            </td>
                            <td>
                                @if ($sale->aging_90_plus > 0)
                                    <div class="text-end">
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">{{ currency($sale->aging_90_plus) }}</span>
                                    </div>
                                @else
                                    <div class="text-end text-muted">-</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center py-4">
                                <p class="text-muted mb-0">No outstanding invoices found for the selected criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-group-divider">
                    <tr class="bg-light">
                        <th colspan="7" class="ps-3"><strong>TOTALS</strong></th>
                        <th>
                            <div class="text-end fw-bold">{{ currency($totalInvoiceAmount) }}</div>
                        </th>
                        <th>
                            <div class="text-end text-success fw-bold">{{ currency($totalAmountPaid) }}</div>
                        </th>
                        <th>
                            <div class="text-end text-danger fw-bold">{{ currency($totalOutstanding) }}</div>
                        </th>
                        <th>
                            @if ($total0to30 > 0)
                                <div class="text-end">
                                    <span class="badge bg-info bg-opacity-10 text-info fw-bold">{{ currency($total0to30) }}</span>
                                </div>
                            @else
                                <div class="text-end text-muted">-</div>
                            @endif
                        </th>
                        <th>
                            @if ($total31to60 > 0)
                                <div class="text-end">
                                    <span class="badge bg-warning bg-opacity-10 text-warning fw-bold">{{ currency($total31to60) }}</span>
                                </div>
                            @else
                                <div class="text-end text-muted">-</div>
                            @endif
                        </th>
                        <th>
                            @if ($total61to90 > 0)
                                <div class="text-end">
                                    <span class="badge bg-orange bg-opacity-10 text-orange fw-bold">{{ currency($total61to90) }}</span>
                                </div>
                            @else
                                <div class="text-end text-muted">-</div>
                            @endif
                        </th>
                        <th>
                            @if ($total90Plus > 0)
                                <div class="text-end">
                                    <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">{{ currency($total90Plus) }}</span>
                                </div>
                            @else
                                <div class="text-end text-muted">-</div>
                            @endif
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
        {{ $sales->links() }}
    </div>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.customer_aging_table_change').on('change keyup', function() {
                    Livewire.dispatch('customerAgingFilterChanged', [
                        $('#from_date').val(),
                        $('#to_date').val(),
                        $('#customer_id').val() || null,
                        $('#table_branch_id').val() || null
                    ]);
                });
            });
        </script>
    @endpush
</div>

