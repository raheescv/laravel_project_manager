<div>
    <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold" id="tailoringCategoryModalLabel">
            <i class="demo-psi-gear fs-4 me-2 text-primary"></i>
            {{ $categories['id'] ?? null ? 'Edit Tailoring Category' : 'Create New Tailoring Category' }}
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
                        <input type="text" class="form-control @error('categories.name') is-invalid @enderror" id="name" placeholder="Category Name" required wire:model="categories.name">
                        <label for="name">Category Name</label>
                        @error('categories.name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating">
                        <textarea class="form-control @error('categories.description') is-invalid @enderror" id="description" placeholder="Description" wire:model="categories.description" style="min-height: 80px;"></textarea>
                        <label for="description">Description</label>
                        @error('categories.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input type="number" class="form-control @error('categories.order') is-invalid @enderror" id="order" placeholder="0" wire:model="categories.order">
                        <label for="order">Order</label>
                        @error('categories.order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" wire:model="categories.is_active">
                        <label class="form-check-label" for="is_active">Active</label>
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
                    <span>Save Category</span>
                </button>
            </div>
        </div>
    </form>
</div>
