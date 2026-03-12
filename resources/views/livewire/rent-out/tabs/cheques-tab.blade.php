<div>
    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">#</th>
                    <th class="fw-semibold py-2">Cheque No</th>
                    <th class="fw-semibold py-2">Bank</th>
                    <th class="fw-semibold py-2">Date</th>
                    <th class="fw-semibold py-2 text-end">Amount</th>
                    <th class="fw-semibold py-2">Payee</th>
                    <th class="fw-semibold py-2">Status</th>
                    <th class="fw-semibold py-2">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->cheques as $index => $cheque)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-check-square-o me-1 text-muted opacity-75"></i>{{ $cheque->cheque_no }}</td>
                        <td>{{ $cheque->bank_name }}</td>
                        <td>{{ $cheque->date?->format('d-m-Y') }}</td>
                        <td class="text-end fw-medium">{{ number_format($cheque->amount, 2) }}</td>
                        <td>{{ $cheque->payee_name }}</td>
                        <td>
                            @if($cheque->status)
                                <span class="badge bg-{{ $cheque->status->color() }}">{{ $cheque->status->label() }}</span>
                            @endif
                        </td>
                        <td>{{ $cheque->remarks }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No cheques found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
