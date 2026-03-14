<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="small text-muted">
            <i class="fa fa-cogs me-1"></i> Services & Charges
        </div>
        <div class="d-flex gap-1 flex-wrap">
            <button type="button" class="btn btn-sm btn-primary" wire:click="openServiceModal">
                <i class="fa fa-plus me-1"></i> Add Service
            </button>
            <button type="button" class="btn btn-sm btn-info text-white" wire:click="openServiceChargeModal">
                <i class="fa fa-calculator me-1"></i> Service Charge
            </button>
            <button type="button" class="btn btn-sm btn-success" wire:click="openServicePaymentModal">
                <i class="fa fa-money me-1"></i> Pay Existing
            </button>
        </div>
    </div>

    {{-- Category Summary --}}
    @if($categorySummary->count() > 0)
        <div class="mb-3">
            <h6 class="fw-bold small text-muted mb-2"><i class="fa fa-bar-chart me-1"></i> Services Summary</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="bg-light text-muted">
                        <tr class="small">
                            <th class="fw-semibold py-1">Category</th>
                            <th class="fw-semibold py-1 text-end">Credit</th>
                            <th class="fw-semibold py-1 text-end">Debit</th>
                            <th class="fw-semibold py-1 text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorySummary as $row)
                            @php $balance = $row->credit - $row->debit; @endphp
                            @if($balance != 0)
                                <tr class="small">
                                    <td>{{ $row->category }}</td>
                                    <td class="text-end text-success">{{ number_format($row->credit, 2) }}</td>
                                    <td class="text-end text-danger">{{ number_format($row->debit, 2) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($balance, 2) }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Service Transactions --}}
    @if($servicePayments->count() > 0)
        <h6 class="fw-bold small text-muted mb-2"><i class="fa fa-exchange me-1"></i> Service Transactions</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr class="text-capitalize small">
                        <th class="fw-semibold py-2">#</th>
                        <th class="fw-semibold py-2">Date</th>
                        <th class="fw-semibold py-2">Source</th>
                        <th class="fw-semibold py-2">Category</th>
                        <th class="fw-semibold py-2">Payment Mode</th>
                        <th class="fw-semibold py-2 text-end">Credit</th>
                        <th class="fw-semibold py-2 text-end">Debit</th>
                        <th class="fw-semibold py-2">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($servicePayments as $index => $payment)
                        <tr>
                            <td class="small">{{ $index + 1 }}</td>
                            <td class="small">{{ $payment->date?->format('d-m-Y') ?? '' }}</td>
                            <td>
                                <span class="badge bg-{{ match($payment->source) {
                                    'ServiceCharge' => 'info',
                                    'Service' => 'secondary',
                                    default => 'light text-dark',
                                } }} bg-opacity-75 small">{{ $payment->source }}</span>
                            </td>
                            <td class="small">{{ $payment->category ?? '' }}</td>
                            <td class="small">{{ $payment->account?->name ?? '' }}</td>
                            <td class="text-end text-success fw-medium small">
                                {{ $payment->credit > 0 ? number_format($payment->credit, 2) : '' }}
                            </td>
                            <td class="text-end text-danger fw-medium small">
                                {{ $payment->debit > 0 ? number_format($payment->debit, 2) : '' }}
                            </td>
                            <td class="small text-muted">{{ $payment->remark ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="5" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success">{{ number_format($servicePayments->sum('credit'), 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($servicePayments->sum('debit'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center text-muted py-3 small">No service transactions found</div>
    @endif
</div>
