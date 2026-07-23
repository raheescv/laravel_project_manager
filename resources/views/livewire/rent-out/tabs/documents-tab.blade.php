<div>
    <div class="row g-3">
        {{-- ==================== Mandatory Document panel (booking page only) ==================== --}}
        @if ($isBooking)
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-success text-white border-0 d-flex align-items-center justify-content-between py-2 px-3">
                        <span class="fw-semibold d-inline-flex align-items-center gap-2" style="font-size: .82rem;">
                            <i class="fa fa-shield"></i> Mandatory Documents
                        </span>
                        <button type="button"
                            class="btn btn-light btn-sm fw-semibold d-inline-flex align-items-center"
                            style="font-size: .68rem;" wire:click="openMandatoryModal">
                            <i class="fa fa-sliders me-1"></i> Configure
                        </button>
                    </div>

                    <div class="card-body p-3">
                        @if ($mandatoryDocuments->isEmpty())
                            <div class="text-center text-muted py-4 px-2">
                                <i class="fa fa-list-alt d-block mb-2 opacity-50" style="font-size: 1.6rem;"></i>
                                <div class="small fw-medium">No mandatory documents set</div>
                                <div class="text-muted mb-0" style="font-size: .7rem;">
                                    Set a tenant default in Settings &rsaquo; Document Types, or click Configure for this
                                    booking.
                                </div>
                            </div>
                        @else
                            @php
                                $mandTotal = $mandatoryDocuments->count();
                                $mandDone = $mandatoryDocuments->where('done', true)->count();
                                $mandPct = $mandTotal ? round(($mandDone / $mandTotal) * 100) : 0;
                                $allDone = $mandDone === $mandTotal;
                            @endphp

                            {{-- Completion progress --}}
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="text-muted" style="font-size: .72rem;">Completion</span>
                                <span class="fw-semibold {{ $allDone ? 'text-success' : 'text-warning-emphasis' }}"
                                    style="font-size: .72rem;">{{ $mandDone }}/{{ $mandTotal }} uploaded</span>
                            </div>
                            <div class="progress rounded-pill mb-3" role="progressbar"
                                aria-valuenow="{{ $mandPct }}" aria-valuemin="0" aria-valuemax="100"
                                style="height: 8px;">
                                <div class="progress-bar {{ $allDone ? 'bg-success' : 'bg-warning' }}"
                                    style="width: {{ $mandPct }}%;"></div>
                            </div>

                            <div class="text-uppercase text-muted fw-semibold mb-1"
                                style="font-size: .6rem; letter-spacing: .05em;">Checklist</div>
                            <ul class="list-group list-group-flush border rounded-3 overflow-hidden">
                                @foreach ($mandatoryDocuments as $mand)
                                    <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-2">
                                        <span class="d-inline-flex align-items-center gap-2 text-truncate">
                                            <i class="fa {{ $mand->done ? 'fa-check-circle text-success' : 'fa-circle-o text-secondary opacity-50' }}"></i>
                                            <span class="fw-medium text-truncate"
                                                style="font-size: .78rem;">{{ $mand->name }}</span>
                                        </span>
                                        @if ($mand->done)
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis flex-shrink-0"
                                                style="font-size: .6rem;"><i class="fa fa-check me-1"></i>Done</span>
                                        @else
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis flex-shrink-0"
                                                style="font-size: .6rem;"><i class="fa fa-clock-o me-1"></i>Pending</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- ==================== Documents panel ==================== --}}
        <div class="col-12 {{ $isBooking ? 'col-lg-8' : '' }}">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-body border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 py-2 px-3">
                    <span class="fw-semibold d-inline-flex align-items-center gap-2" style="font-size: .85rem;">
                        <i class="fa fa-folder-open text-primary"></i> Documents
                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"
                            style="font-size: .62rem;">{{ $documents->count() }}</span>
                    </span>
                    <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center"
                        style="font-size: .74rem;" wire:click="openDocumentModal">
                        <i class="fa fa-upload me-1"></i> Add New Document
                    </button>
                </div>

                <div class="card-body p-3">
                    {{-- Filter & bulk actions --}}
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <div class="form-check m-0 d-inline-flex align-items-center gap-1">
                            <input type="checkbox" class="form-check-input mt-0" id="docSelectAll"
                                wire:model.live="selectAll">
                            <label class="form-check-label text-muted small" for="docSelectAll">All</label>
                        </div>
                        <div wire:ignore style="min-width: 220px;">
                            {{ html()->select('document_type_id', [])->value('')->class('select-document_type_id')->id('docTabFilterDocumentType')->placeholder('All Document Types') }}
                        </div>

                        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center ms-auto"
                            wire:click="deleteSelected"
                            wire:confirm="Are you sure you want to delete the selected ({{ count($selectedDocs) }}) documents?"
                            style="font-size: .74rem;" @disabled(count($selectedDocs) === 0)>
                            <i class="fa fa-trash me-1"></i> Delete Selected
                            @if (count($selectedDocs) > 0)
                                <span class="badge bg-danger ms-1"
                                    style="font-size: .6rem;">{{ count($selectedDocs) }}</span>
                            @endif
                        </button>
                    </div>

                    {{-- Document card grid --}}
                    <div class="row g-2">
                        @forelse($documents as $document)
                            @php
                                $ext = strtolower(pathinfo($document->name, PATHINFO_EXTENSION));
                                $ftMap = [
                                    ['icon' => 'fa-file-image-o', 'cls' => 'bg-primary-subtle text-primary', 'label' => 'Image', 'exts' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']],
                                    ['icon' => 'fa-file-pdf-o', 'cls' => 'bg-danger-subtle text-danger', 'label' => 'PDF', 'exts' => ['pdf']],
                                    ['icon' => 'fa-file-word-o', 'cls' => 'bg-success-subtle text-success', 'label' => 'Document', 'exts' => ['doc', 'docx', 'txt', 'rtf', 'odt']],
                                    ['icon' => 'fa-file-excel-o', 'cls' => 'bg-success-subtle text-success', 'label' => 'Sheet', 'exts' => ['xls', 'xlsx', 'csv']],
                                ];
                                $ft = ['icon' => 'fa-file-o', 'cls' => 'bg-secondary-subtle text-secondary', 'label' => strtoupper($ext ?: 'File')];
                                foreach ($ftMap as $cand) {
                                    if (in_array($ext, $cand['exts'])) {
                                        $ft = $cand;
                                        break;
                                    }
                                }
                                $isSelected = in_array((string) $document->id, $selectedDocs, true);
                            @endphp
                            <div class="col-12 col-md-6 {{ $isBooking ? 'col-xxl-6' : 'col-xxl-4' }}">
                                <div class="card border shadow-sm rounded-3 h-100 {{ $isSelected ? 'border-primary' : '' }}">
                                    <div class="card-body p-3 d-flex flex-column">
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="form-check m-0">
                                                <input type="checkbox" class="form-check-input"
                                                    value="{{ $document->id }}" wire:model.live="selectedDocs">
                                            </div>
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-2 flex-shrink-0 {{ $ft['cls'] }}"
                                                style="width: 36px; height: 36px;">
                                                <i class="fa {{ $ft['icon'] }}"></i>
                                            </span>
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <div class="fw-semibold text-truncate"
                                                    title="{{ $document->name }}">{{ $document->name }}</div>
                                                <div class="text-muted text-truncate" style="font-size: .68rem;">
                                                    {{ $ft['label'] }} · {{ $document->created_at?->format('d M Y, h:i A') }}
                                                </div>
                                            </div>
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border flex-shrink-0"
                                                style="font-size: .6rem;">{{ $document->documentType?->name ?? '—' }}</span>
                                        </div>

                                        @if (filled($document->remarks))
                                            <div class="text-muted small mt-2 text-truncate"
                                                title="{{ $document->remarks }}">{{ $document->remarks }}</div>
                                        @else
                                            <div class="text-muted fst-italic small mt-2 opacity-50">No remarks</div>
                                        @endif

                                        <div class="d-flex gap-1 pt-2 border-top mt-auto">
                                            <a href="{{ $document->url }}" target="_blank"
                                                class="btn btn-light btn-sm flex-fill" title="View">
                                                <i class="fa fa-eye text-info me-1"></i>View
                                            </a>
                                            <button type="button" class="btn btn-light btn-sm flex-fill"
                                                wire:click="downloadDocument({{ $document->id }})" title="Download">
                                                <i class="fa fa-download text-primary me-1"></i>Get
                                            </button>
                                            <button type="button" class="btn btn-light btn-sm"
                                                wire:click="editDocument({{ $document->id }})" title="Edit">
                                                <i class="fa fa-pencil text-warning"></i>
                                            </button>
                                            <button type="button" class="btn btn-light btn-sm"
                                                wire:click="deleteDocument({{ $document->id }})"
                                                wire:confirm="Are you sure you want to delete this document?"
                                                title="Delete">
                                                <i class="fa fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center text-muted py-5">
                                    <i class="fa fa-inbox d-block mb-2 opacity-50" style="font-size: 1.8rem;"></i>
                                    <div class="small fw-medium">No documents uploaded yet</div>
                                    <div class="text-muted" style="font-size: .72rem;">Click
                                        <strong>Add New Document</strong> to upload the first one.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
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
