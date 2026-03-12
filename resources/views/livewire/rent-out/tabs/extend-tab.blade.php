<div>
    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">#</th>
                    <th class="fw-semibold py-2">Start Date</th>
                    <th class="fw-semibold py-2">End Date</th>
                    <th class="fw-semibold py-2 text-end">Rent Amount</th>
                    <th class="fw-semibold py-2">Payment Mode</th>
                    <th class="fw-semibold py-2">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->extends as $index => $extend)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $extend->start_date?->format('d-m-Y') }}</td>
                        <td><i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $extend->end_date?->format('d-m-Y') }}</td>
                        <td class="text-end fw-medium">{{ number_format($extend->rent_amount, 2) }}</td>
                        <td>{{ $extend->payment_mode?->label() }}</td>
                        <td>{{ $extend->remarks }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No extensions found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
