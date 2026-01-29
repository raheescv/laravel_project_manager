<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="tailoringCategoryModelModalLabel">
            <i class="demo-psi-box fs-4 me-2 text-primary"></i>
            {{ $models['id'] ?? null ? 'Edit Category Model' : 'Add Category Model' }}
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
                <div class="col-12">
                    <div class="form-floating">
                        <select class="form-control @error('models.tailoring_category_id') is-invalid @enderror" id="models_tailoring_category_id" wire:model="models.tailoring_category_id">
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <label for="models_tailoring_category_id">Category</label>
                        @error('models.tailoring_category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <input type="text" class="form-control @error('models.name') is-invalid @enderror" id="model_name" placeholder="Model Name" required wire:model="models.name">
                        <label for="model_name">Model Name</label>
                        @error('models.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control @error('models.description') is-invalid @enderror" id="model_description" placeholder="Description" wire:model="models.description" style="min-height: 80px;"></textarea>
                        <label for="model_description">Description</label>
                        @error('models.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="model_is_active" wire:model="models.is_active">
                        <label class="form-check-label" for="model_is_active">Active</label>
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
                    <span>Save Model</span>
                </button>
            </div>
        </div>
    </form>
</div>
