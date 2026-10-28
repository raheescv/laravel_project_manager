<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="small text-muted">
            <i class="fa fa-line-chart me-1"></i> Return on Investment
        </div>
        <div class="d-flex gap-1 flex-wrap">
            @can('rent out roi.create')
                <button type="button" class="btn btn-outline-success d-inline-flex align-items-center"
                    style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                    wire:click="openRoiModal">
                    <i class="fa fa-plus me-1"></i> Add ROI
                </button>
            @endcan
            @can('rent out roi.payment')
                <button type="button" class="btn btn-outline-warning d-inline-flex align-items-center"
                    style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                    wire:click="openRoiPaymentModal" @disabled($totals['balance'] <= 0)>
                    <i class="fa fa-share me-1"></i> Pay ROI
                </button>
            @endcan
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="border rounded-3 px-3 py-2 h-100">
                <div class="text-muted small">Total ROI (Credit)</div>
                <div class="fw-bold text-success">{{ number_format($totals['accrued'], 2) }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded-3 px-3 py-2 h-100">
                <div class="text-muted small">Paid (Debit)</div>
                <div class="fw-bold text-danger">{{ number_format($totals['paid'], 2) }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="border rounded-3 px-3 py-2 h-100">
                <div class="text-muted small">Balance Payable</div>
                <div class="fw-bold">{{ number_format($totals['balance'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- ROI Ledger --}}
    <h6 class="fw-bold small text-muted mb-2"><i class="fa fa-exchange me-1"></i> ROI Transactions</h6>
    @if ($entries->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle border-bottom mb-0 table-sm">
                <thead class="bg-light text-muted">
                    <tr class="text-capitalize small">
                        <th class="fw-semibold py-2">#</th>
                        <th class="fw-semibold py-2">Date</th>
                        <th class="fw-semibold py-2">Type</th>
                        <th class="fw-semibold py-2 d-none d-md-table-cell">Details</th>
                        <th class="fw-semibold py-2 text-end">Credit</th>
                        <th class="fw-semibold py-2 text-end">Debit</th>
                        <th class="fw-semibold py-2 text-end">Balance</th>
                        <th class="fw-semibold py-2 d-none d-lg-table-cell">Remark</th>
                        <th class="fw-semibold py-2 text-center" style="width: 70px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $index => $entry)
                        @php
                            $transaction = $entry['transaction'];
                            $roi = $entry['roi'];
                            $isPayment = $transaction->source === \App\Models\RentOutRoi::PAYMENT_SOURCE;
                        @endphp
                        <tr wire:key="roi-ledger-{{ $transaction->id }}">
                            <td class="small">{{ $index + 1 }}</td>
                            <td class="small text-nowrap">{{ $transaction->date?->format('d-m-Y') }}</td>
                            <td class="text-nowrap">
                                <span class="badge bg-{{ $isPayment ? 'warning text-dark' : 'success' }} bg-opacity-75 small">{{ $isPayment ? 'Payment' : 'ROI' }}</span>
                            </td>
                            <td class="small text-nowrap d-none d-md-table-cell">
                                @if ($isPayment)
                                    {{ $transaction->account?->name }}
                                @elseif ($roi)
                                    {{ $roi->start_date?->format('d-m-Y') }} → {{ $roi->end_date?->format('d-m-Y') }}
                                    <span class="text-muted">· {{ $roi->no_of_months }} × {{ number_format($roi->per_month_roi, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-end text-success fw-medium small">
                                {{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '' }}
                            </td>
                            <td class="text-end text-danger fw-medium small">
                                {{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '' }}
                            </td>
                            <td class="text-end fw-bold small">{{ number_format($entry['balance'], 2) }}</td>
                            <td class="small text-muted d-none d-lg-table-cell">{{ $transaction->remark }}</td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-sm btn-link text-info p-0 me-2" title="{{ $isPayment ? 'Print Voucher' : 'Print Invoice' }}"
                                    wire:click="printDocument({{ $transaction->id }})">
                                    <i class="fa {{ $isPayment ? 'fa-print' : 'fa-file-text-o' }}"></i>
                                </button>
                                @can('rent out roi.delete')
                                    @if ($isPayment)
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" title="Delete"
                                            wire:click="deletePayment({{ $transaction->id }})"
                                            wire:confirm="Are you sure you want to delete this ROI payment?">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @elseif ($roi)
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" title="Delete"
                                            wire:click="deleteRoi({{ $roi->id }})"
                                            wire:confirm="Are you sure you want to delete this ROI?">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="3" class="py-2 text-end">Total</td>
                        <td class="d-none d-md-table-cell"></td>
                        <td class="py-2 text-end text-success">{{ number_format($totals['accrued'], 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($totals['paid'], 2) }}</td>
                        <td class="py-2 text-end">{{ number_format($totals['balance'], 2) }}</td>
                        <td class="d-none d-lg-table-cell"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div class="text-center text-muted py-3 small">No ROI transactions yet</div>
    @endif
</div>

@script
<script>
    $wire.on('open-receipt-tab', (data) => {
        window.open(data.url, '_blank');
    });
</script>
@endscript
