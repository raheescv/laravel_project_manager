<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-check-square-o me-1"></i>
            <strong>{{ $rentOut->cheques->count() }}</strong> cheque(s)
            &middot; Total: <strong class="text-primary">{{ number_format($rentOut->cheques->sum('amount'), 2) }}</strong>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary shadow-sm" wire:click="openSingleChequeModal">
                <i class="fa fa-plus me-1"></i> Single Cheque
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" wire:click="openMultipleChequeModal">
                <i class="fa fa-copy me-1"></i> Multiple Cheques
            </button>
        </div>
    </div>

    {{-- Table --}}
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
                    <th class="fw-semibold py-2 text-center" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->cheques as $index => $cheque)
                    <tr class="{{ $cheque->status?->value === 'cleared' ? 'table-success' : ($cheque->status?->value === 'bounce' || $cheque->status?->value === 'return' ? 'table-danger' : '') }}">
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-check-square-o me-1 text-muted opacity-75"></i>{{ $cheque->cheque_no }}</td>
                        <td>{{ $cheque->bank_name }}</td>
                        <td>{{ $cheque->date?->format('d-m-Y') }}</td>
                        <td class="text-end fw-medium">{{ number_format($cheque->amount, 2) }}</td>
                        <td>{{ $cheque->payee_name }}</td>
                        <td>
                            <div class="dropdown d-inline-block">
                                <button class="badge bg-{{ $cheque->status?->color() }} border-0 dropdown-toggle"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ $cheque->status?->label() }}
                                </button>
                                <ul class="dropdown-menu dropdown-menu-sm">
                                    @foreach ($chequeStatuses as $status)
                                        <li>
                                            <button class="dropdown-item small {{ $cheque->status?->value === $status->value ? 'active' : '' }}"
                                                wire:click="updateStatus({{ $cheque->id }}, '{{ $status->value }}')">
                                                <span class="badge bg-{{ $status->color() }} me-1">&nbsp;</span>
                                                {{ $status->label() }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </td>
                        <td>{{ $cheque->remarks }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-light btn-sm" wire:click="editCheque({{ $cheque->id }})" title="Edit">
                                    <i class="fa fa-pencil text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm" wire:click="deleteCheque({{ $cheque->id }})"
                                    wire:confirm="Are you sure you want to delete this cheque?" title="Delete">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">No cheques found</td></tr>
                @endforelse
            </tbody>
            @if($rentOut->cheques->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="4" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-primary">{{ number_format($rentOut->cheques->sum('amount'), 2) }}</td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
