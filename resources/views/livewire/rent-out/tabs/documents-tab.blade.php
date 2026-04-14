<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="small text-muted">
            <i class="fa fa-file-o me-1"></i>
            <strong>{{ $documents->count() }}</strong> document(s)
        </div>
    </div>

    {{-- Filter & Action Buttons --}}
    <div class="d-flex flex-wrap gap-1 mb-2 align-items-center">
        <div wire:ignore style="min-width: 200px;">
            {{ html()->select('document_type_id', [])->value('')->class('select-document_type_id')->id('docTabFilterDocumentType')->placeholder('All Document Types') }}
        </div>

        <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center"
            style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
            wire:click="deleteSelected"
            wire:confirm="Are you sure you want to delete the selected ({{ count($selectedDocs) }}) documents?">
            <i class="fa fa-trash me-1"></i> Delete Selected
            @if (count($selectedDocs) > 0)
                <span class="badge bg-danger ms-1" style="font-size: .6rem;">{{ count($selectedDocs) }}</span>
            @endif
        </button>

        <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center ms-auto"
            style="font-size: .7rem; padding: .2rem .5rem; border-radius: 4px;"
            wire:click="openDocumentModal">
            <i class="fa fa-upload me-1"></i> Upload Document
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle border-bottom mb-0 table-sm">
            <thead class="bg-light text-muted">
                <tr class="text-capitalize small">
                    <th class="fw-semibold py-2 text-center" style="width: 35px;">
                        <input type="checkbox" class="form-check-input" wire:model.live="selectAll">
                    </th>
                    <th class="fw-semibold py-2">#</th>
                    <th class="fw-semibold py-2">Name</th>
                    <th class="fw-semibold py-2">Document Type</th>
                    <th class="fw-semibold py-2">Remarks</th>
                    <th class="fw-semibold py-2">Upload Date</th>
                    <th class="fw-semibold py-2 text-center" style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $index => $document)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input" value="{{ $document->id }}"
                                wire:model.live="selectedDocs">
                        </td>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <i class="fa fa-file-o me-1 text-muted opacity-75"></i>{{ $document->name }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $document->documentType?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="small text-muted">{{ $document->remarks }}</td>
                        <td>{{ $document->created_at?->format('d-m-Y h:i A') }}</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ $document->url }}" class="btn btn-light btn-sm" target="_blank"
                                    title="View">
                                    <i class="fa fa-eye text-info"></i>
                                </a>
                                <button type="button" class="btn btn-light btn-sm"
                                    wire:click="downloadDocument({{ $document->id }})" title="Download">
                                    <i class="fa fa-download text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-sm"
                                    wire:click="deleteDocument({{ $document->id }})"
                                    wire:confirm="Are you sure you want to delete this document?" title="Delete">
                                    <i class="fa fa-trash text-danger"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">No documents found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#docTabFilterDocumentType').on('change', function() {
                    @this.set('filterDocumentTypeId', $(this).val());
                });
            });
        </script>
    @endpush
</div>
