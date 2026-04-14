<div>
    <div class="modal-header py-2 px-3 text-white border-0"
        style="background: linear-gradient(135deg, #3a9e7a, #2e7d56);">
        <h6 class="modal-title fw-bold mb-0">
            <i class="fa fa-cloud-upload me-2"></i> Upload Document
        </h6>
        <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body p-3">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-file me-1 text-muted"></i> Choose File <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control form-control-sm" wire:model="file">
                <small class="text-muted">Max file size: 10MB</small>
                @error('file')
                    <br><small class="text-danger">{{ $message }}</small>
                @enderror
                <div wire:loading wire:target="file" class="mt-1">
                    <small class="text-primary">
                        <i class="fa fa-spinner fa-spin me-1"></i>Uploading...
                    </small>
                </div>
            </div>
            <div class="col-12" wire:ignore>
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-tag me-1 text-muted"></i> Document Type <span class="text-danger">*</span>
                </label>
                <select class="select-document_type_id" id="docModalDocumentTypeSelect" placeholder="Search Here">
                    <option value="">Select</option>
                </select>
                @error('documentTypeId')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold small mb-1">
                    <i class="fa fa-comment-o me-1 text-muted"></i> Remarks
                </label>
                <textarea class="form-control form-control-sm" wire:model="remarks" rows="2" placeholder="Optional remarks..."></textarea>
            </div>
        </div>
    </div>

    <div class="modal-footer py-2 px-3 border-top">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
            <i class="fa fa-times me-1"></i> Close
        </button>
        <button type="button" class="btn btn-sm btn-success" wire:click="save"
            wire:loading.attr="disabled" wire:target="save, file">
            <i class="fa fa-check me-1"></i>
            <span wire:loading.remove wire:target="save">Save</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#docModalDocumentTypeSelect').on('change', function(e) {
                    const value = $(this).val() || null;
                    @this.set('documentTypeId', value);
                });
                window.addEventListener('ClearDocumentModal', event => {
                    var ts = document.querySelector('#docModalDocumentTypeSelect')?.tomselect;
                    if (ts) {
                        ts.clear();
                    }
                });
            });
        </script>
    @endpush
</div>
