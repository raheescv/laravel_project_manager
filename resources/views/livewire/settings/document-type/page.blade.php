<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold">
            <i class="demo-psi-gear fs-4 me-2 text-primary"></i>
            {{ isset($formData['id']) ? 'Edit Document Type' : 'Create New Document Type' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body py-4">
            @if ($errors->any())
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="demo-pli-danger-2 fs-4 me-2"></i>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-md-12">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.name') is-invalid @enderror" id="name" placeholder="Document Type Name" required wire:model="formData.name">
                        <label for="name">Name</label>
                        @error('formData.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('formData.arabic_name') is-invalid @enderror" id="arabic_name" placeholder="Enter Arabic Name" wire:model="formData.arabic_name" dir="rtl">
                        <label for="arabic_name">Arabic Name</label>
                        @error('formData.arabic_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer border-top-0 pt-0">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" wire:click="save(1)" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <i class="demo-psi-repeat-2 fs-5"></i>
                    <span>Save & Add Another</span>
                </button>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4">
                    <i class="demo-psi-save fs-5"></i>
                    <span>Save Document Type</span>
                </button>
            </div>
        </div>
    </form>
</div>
