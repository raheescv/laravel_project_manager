<div>
    {{-- Summary --}}
    @php
        $totalCredit = $rentOut->journals->sum('credit');
        $totalDebit = $rentOut->journals->sum('debit');
        $netBalance = $totalCredit - $totalDebit;
    @endphp
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-exchange me-1"></i>
            <strong>{{ $rentOut->journals->count() }}</strong> transaction(s)
            &middot; Net Balance: <strong class="{{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</strong>
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
                    <th class="fw-semibold py-2">Payment Mode</th>
                    <th class="fw-semibold py-2 text-end">Credit</th>
                    <th class="fw-semibold py-2 text-end">Debit</th>
                    <th class="fw-semibold py-2 text-end">Balance</th>
                    <th class="fw-semibold py-2">Remark</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = 0; @endphp
                @forelse($rentOut->journals->sortBy('date') as $index => $journal)
                    @php
                        $runningBalance += ($journal->credit ?? 0) - ($journal->debit ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $journal->date?->format('d-m-Y') ?? '' }}</td>
                        <td>{{ $journal->category ?? '' }}</td>
                        <td>{{ $journal->payment_mode ?? '' }}</td>
                        <td class="text-end text-success fw-medium">
                            {{ $journal->credit > 0 ? number_format($journal->credit, 2) : '' }}
                        </td>
                        <td class="text-end text-danger fw-medium">
                            {{ $journal->debit > 0 ? number_format($journal->debit, 2) : '' }}
                        </td>
                        <td class="text-end fw-bold {{ $runningBalance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($runningBalance, 2) }}
                        </td>
                        <td>{{ $journal->remark ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No transactions found</td></tr>
                @endforelse
            </tbody>
            @if($rentOut->journals->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="4" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-success">{{ number_format($totalCredit, 2) }}</td>
                        <td class="py-2 text-end text-danger">{{ number_format($totalDebit, 2) }}</td>
                        <td class="py-2 text-end {{ $netBalance >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($netBalance, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
