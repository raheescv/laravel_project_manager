<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-plus-circle me-1"></i>
            <strong>{{ $rentOut->extends->count() }}</strong> extension(s)
        </div>
        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center"
            style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
            wire:click="openExtendModal">
            <i class="fa fa-plus me-1"></i> Add Extension
        </button>
    </div>

    {{-- Table --}}
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
                    <th class="fw-semibold py-2 text-center" style="width: 70px;">Actions</th>
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
                        <td class="text-center">
                            <button type="button" class="btn btn-light btn-sm" wire:click="deleteExtend({{ $extend->id }})"
                                wire:confirm="Are you sure you want to delete this extension?" title="Delete">
                                <i class="fa fa-trash text-danger"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No extensions found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
