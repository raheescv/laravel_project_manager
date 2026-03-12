{{-- Security Tab --}}
<div class="table-responsive">
    <table class="table table-hover align-middle border-bottom mb-0 table-sm">
        <thead class="bg-light text-muted">
            <tr class="text-capitalize small">
                <th class="fw-semibold py-2">#</th>
                <th class="fw-semibold py-2 text-end">Amount</th>
                <th class="fw-semibold py-2">Payment Mode</th>
                <th class="fw-semibold py-2">Type</th>
                <th class="fw-semibold py-2">Status</th>
                <th class="fw-semibold py-2">Due Date</th>
                <th class="fw-semibold py-2">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentOut->securities as $index => $security)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-end fw-medium">{{ number_format($security->amount, 2) }}</td>
                    <td>{{ $security->payment_mode?->label() }}</td>
                    <td>{{ $security->type?->label() }}</td>
                    <td>
                        <span class="badge bg-{{ $security->status?->color() }}">
                            {{ $security->status?->label() }}
                        </span>
                    </td>
                    <td>{{ $security->due_date?->format('d-m-Y') }}</td>
                    <td>{{ $security->remarks }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No security deposits found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
