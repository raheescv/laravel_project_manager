<div>
    {{-- Summary --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="small text-muted">
            <i class="fa fa-wrench me-1"></i>
            <strong>{{ count($rentOut->maintenances ?? []) }}</strong> maintenance record(s)
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">#</th>
                    <th class="fw-semibold py-2">Date</th>
                    <th class="fw-semibold py-2">Description</th>
                    <th class="fw-semibold py-2 text-end">Amount</th>
                    <th class="fw-semibold py-2">Status</th>
                    <th class="fw-semibold py-2">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->maintenances ?? [] as $index => $maintenance)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-calendar me-1 text-muted opacity-75"></i>{{ $maintenance->date?->format('d-m-Y') }}</td>
                        <td>{{ $maintenance->description }}</td>
                        <td class="text-end fw-medium">{{ number_format($maintenance->amount, 2) }}</td>
                        <td>
                            @if($maintenance->status)
                                <span class="badge bg-{{ $maintenance->status->color() }}">{{ $maintenance->status->label() }}</span>
                            @endif
                        </td>
                        <td>{{ $maintenance->remarks }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No maintenance records found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
