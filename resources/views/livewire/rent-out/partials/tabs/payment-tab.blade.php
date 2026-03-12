{{-- Payment Tab --}}
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
                    <td class="text-end text-success fw-medium">{{ number_format($journal->credit ?? 0, 2) }}</td>
                    <td class="text-end text-danger fw-medium">{{ number_format($journal->debit ?? 0, 2) }}</td>
                    <td>{{ $journal->remark ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No payment records found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
