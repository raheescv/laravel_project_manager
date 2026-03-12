{{-- Utilities Tab --}}
<div class="table-responsive">
    <table class="table table-hover align-middle border-bottom mb-0 table-sm">
        <thead class="bg-light text-muted">
            <tr class="text-capitalize small">
                <th class="fw-semibold py-2">#</th>
                <th class="fw-semibold py-2">Utility</th>
                <th class="fw-semibold py-2">Date</th>
                <th class="fw-semibold py-2 text-end">Amount</th>
                <th class="fw-semibold py-2 text-end">Balance</th>
                <th class="fw-semibold py-2">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentOut->utilityTerms as $index => $uTerm)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><i class="fa fa-bolt me-1 text-warning opacity-75"></i>{{ $uTerm->utility?->name ?? '' }}</td>
                    <td>{{ $uTerm->date?->format('d-m-Y') }}</td>
                    <td class="text-end fw-medium">{{ number_format($uTerm->amount, 2) }}</td>
                    <td class="text-end fw-medium {{ $uTerm->balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($uTerm->balance, 2) }}</td>
                    <td>{{ $uTerm->remarks }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No utility records found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
