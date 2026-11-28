<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-plus-circle me-1"></i>
            <strong>{{ $rentOut->extends->count() }}</strong> extension(s)
        </div>
        @can($permissionPrefix.'.extend')
            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center"
                style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
                wire:click="openExtendModal">
                <i class="fa fa-plus me-1"></i> Add Extension
            </button>
        @endcan
    </div>

    @php
        $latestExtendId = $rentOut->extends->max('id');
    @endphp

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
                    <th class="fw-semibold py-2 text-center" style="width: 120px;">Actions</th>
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
                        <td class="text-center text-nowrap">
                            @if ($extend->id === $latestExtendId)
                                @can($permissionPrefix.'.residential lease')
                                    <a href="{{ route('print::rentout::residential-lease', [$rentOut->id, 'extended']) }}" target="_blank"
                                        class="btn btn-light btn-sm" title="{{ $permissionPrefix === 'rent out' ? 'Residential Lease' : 'Sales Agreement' }}">
                                        <i class="fa fa-print text-primary"></i>
                                    </a>
                                @endcan
                            @endif
                            @can($permissionPrefix.'.extend edit')
                                <button type="button" class="btn btn-light btn-sm" wire:click="editExtend({{ $extend->id }})" title="Edit">
                                    <i class="fa fa-pencil text-warning"></i>
                                </button>
                            @endcan
                            @can($permissionPrefix.'.extend delete')
                                <button type="button" class="btn btn-light btn-sm" wire:click="deleteExtend({{ $extend->id }})"
                                    wire:confirm="Are you sure you want to delete this extension?" title="Delete">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No extensions found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
