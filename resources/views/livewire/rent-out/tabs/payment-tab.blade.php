<div>
    {{-- Summary --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-credit-card me-1"></i>
            <strong>{{ $rentOut->journals->count() }}</strong> payment record(s)
            &middot; Credit: <strong class="text-success">{{ number_format($rentOut->journals->sum('credit'), 2) }}</strong>
            &middot; Debit: <strong class="text-danger">{{ number_format($rentOut->journals->sum('debit'), 2) }}</strong>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">#</th>
                    <th class="fw-semibold py-2">Date</th>
                    <th class="fw-semibold py-2">Category</th>
                    <th class="fw-semibold py-2">Due Date</th>
                    <th class="fw-semibold py-2">Payment Mode</th>
                    <th class="fw-semibold py-2 text-end">Credit</th>
                    <th class="fw-semibold py-2 text-end">Debit</th>
                    <th class="fw-semibold py-2">Remark</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->journals as $index => $journal)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $journal->date?->format('d-m-Y') ?? '' }}</td>
                        <td>{{ $journal->category ?? '' }}</td>
                        <td>{{ $journal->due_date?->format('d-m-Y') ?? '' }}</td>
                        <td>{{ $journal->payment_mode ?? '' }}</td>
                        <td class="text-end text-success fw-medium">
                            {{ $journal->credit > 0 ? number_format($journal->credit, 2) : '' }}
                        </td>
                        <td class="text-end text-danger fw-medium">
                            {{ $journal->debit > 0 ? number_format($journal->debit, 2) : '' }}
                        </td>
                        <td>{{ $journal->remark ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No payment records found</td></tr>
                @endforelse
            </tbody>
            @if($rentOut->journals->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="5" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success">{{ number_format($rentOut->journals->sum('credit'), 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($rentOut->journals->sum('debit'), 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
