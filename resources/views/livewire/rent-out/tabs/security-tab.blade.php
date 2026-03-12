<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-shield me-1"></i>
            <strong>{{ $rentOut->securities->count() }}</strong> security deposit(s)
            &middot; Total: <strong class="text-primary">{{ number_format($rentOut->securities->sum('amount'), 2) }}</strong>
        </div>
        <button type="button" class="btn btn-sm btn-primary shadow-sm" wire:click="openSecurityModal">
            <i class="fa fa-plus me-1"></i> Add Security
        </button>
    </div>

    {{-- Table --}}
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
                    <th class="fw-semibold py-2 text-center" style="width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->securities as $index => $security)
                    <tr class="{{ $security->status?->value === 'collected' ? 'table-success' : ($security->status?->value === 'returned' ? 'table-info' : '') }}">
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
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-light btn-sm" wire:click="editSecurity({{ $security->id }})" title="Edit">
                                    <i class="fa fa-pencil text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm" wire:click="deleteSecurity({{ $security->id }})"
                                    wire:confirm="Are you sure you want to delete this security deposit?" title="Delete">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No security deposits found</td></tr>
                @endforelse
            </tbody>
            @if($rentOut->securities->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="1" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-primary">{{ number_format($rentOut->securities->sum('amount'), 2) }}</td>
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
