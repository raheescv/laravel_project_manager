<div>
    {{-- Action Buttons --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="small text-muted">
            <i class="fa fa-cogs me-1"></i>
            <strong>{{ $rentOut->services->count() }}</strong> service(s)
            &middot; Total: <strong class="text-primary">{{ number_format($rentOut->services->sum('amount'), 2) }}</strong>
        </div>
        <button type="button" class="btn btn-sm btn-primary shadow-sm" wire:click="openServiceModal">
            <i class="fa fa-plus me-1"></i> Add Service
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2">#</th>
                    <th class="fw-semibold py-2">Service Name</th>
                    <th class="fw-semibold py-2 text-end">Amount</th>
                    <th class="fw-semibold py-2">Description</th>
                    <th class="fw-semibold py-2 text-center" style="width: 90px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentOut->services as $index => $service)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><i class="fa fa-cog me-1 text-muted opacity-75"></i>{{ $service->name }}</td>
                        <td class="text-end fw-medium">{{ number_format($service->amount, 2) }}</td>
                        <td>{{ $service->description }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-light btn-sm" wire:click="editService({{ $service->id }})" title="Edit">
                                    <i class="fa fa-pencil text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm" wire:click="deleteService({{ $service->id }})"
                                    wire:confirm="Are you sure you want to delete this service?" title="Delete">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No services found</td></tr>
                @endforelse
            </tbody>
            @if($rentOut->services->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold small">
                        <td colspan="2" class="py-2 text-end">Total</td>
                        <td class="py-2 text-end text-primary">{{ number_format($rentOut->services->sum('amount'), 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
