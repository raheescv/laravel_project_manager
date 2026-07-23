<div x-data="{ search: '' }">
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-clipboard me-2"></i> Configure Mandatory Documents
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body p-3">
        <p class="text-muted small mb-3">
            Select the documents that must be collected for this booking. The
            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis" style="font-size: .6rem;">Missing</span>
            / <span class="badge rounded-pill bg-success-subtle text-success-emphasis" style="font-size: .6rem;">Done</span>
            status is tracked automatically against uploaded documents.
        </p>

        @if ($documentTypes->isEmpty())
            <div class="alert alert-warning py-2 px-3 mb-0 small">
                <i class="fa fa-exclamation-triangle me-1"></i> No document types exist yet. Create them in
                Settings &rsaquo; Document Types first.
            </div>
        @else
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <div class="position-relative flex-grow-1" style="min-width: 180px;">
                    <i class="fa fa-search position-absolute text-muted"
                        style="left: 10px; top: 50%; transform: translateY(-50%); font-size: .72rem;"></i>
                    <input type="text" x-model="search" class="form-control form-control-sm ps-4"
                        placeholder="Search document types...">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis" style="font-size: .62rem;">
                        {{ count($selected) }} selected
                    </span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                        style="font-size: .7rem;" wire:click="selectAllTypes">Select all</button>
                    <span class="text-muted">|</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted"
                        style="font-size: .7rem;" wire:click="clearAll">Clear</button>
                </div>
            </div>

            <div class="border rounded-3 p-2" style="max-height: 320px; overflow-y: auto;">
                <div class="row g-2">
                    @foreach ($documentTypes as $type)
                        <div class="col-12 col-sm-6"
                            x-show="search === '' || @js(strtolower($type->name)).includes(search.toLowerCase())">
                            <label
                                class="d-flex align-items-center gap-2 border rounded-2 px-2 py-2 mb-0 rvx-mand-pick w-100"
                                for="mand_doc_{{ $type->id }}">
                                <input class="form-check-input mt-0" type="checkbox" value="{{ $type->id }}"
                                    wire:model.live="selected" id="mand_doc_{{ $type->id }}">
                                <span class="small fw-medium text-body">{{ $type->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="save" wire:loading.attr="disabled"
            wire:target="save">
            <i class="fa fa-check me-1"></i>
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>

    @push('styles')
        <style>
            .rvx-mand-pick {
                cursor: pointer;
                transition: all .12s ease;
            }

            .rvx-mand-pick:hover {
                border-color: #2e7d56 !important;
                background-color: #f4fbf7;
            }
        </style>
    @endpush
</div>
