{{-- Services Tab --}}
<div class="table-responsive">
    <table class="table table-hover align-middle border-bottom mb-0 table-sm">
        <thead class="bg-light text-muted">
            <tr class="text-capitalize small">
                <th class="fw-semibold py-2">#</th>
                <th class="fw-semibold py-2">Service Name</th>
                <th class="fw-semibold py-2 text-end">Amount</th>
                <th class="fw-semibold py-2">Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentOut->services as $index => $service)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><i class="fa fa-cog me-1 text-muted opacity-75"></i>{{ $service->name }}</td>
                    <td class="text-end fw-medium">{{ number_format($service->amount, 2) }}</td>
                    <td>{{ $service->description }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No services found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
