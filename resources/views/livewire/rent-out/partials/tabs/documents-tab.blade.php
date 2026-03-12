{{-- Documents Tab --}}
<div class="table-responsive">
    <table class="table table-hover align-middle border-bottom mb-0 table-sm">
        <thead class="bg-light text-muted">
            <tr class="text-capitalize small">
                <th class="fw-semibold py-2">#</th>
                <th class="fw-semibold py-2">Name</th>
                <th class="fw-semibold py-2">Type</th>
                <th class="fw-semibold py-2">Upload Date</th>
                <th class="fw-semibold py-2 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rentOut->documents ?? [] as $index => $document)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><i class="fa fa-file-o me-1 text-muted opacity-75"></i>{{ $document->name }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $document->type }}</span></td>
                    <td>{{ $document->created_at?->format('d-m-Y') }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ $document->url ?? '#' }}" class="btn btn-light btn-sm" target="_blank" title="View" data-bs-toggle="tooltip">
                                <i class="fa fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No documents found</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
