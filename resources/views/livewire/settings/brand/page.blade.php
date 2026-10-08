<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Brand</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="form-group mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" wire:model.blur="brands.name" placeholder="Name">
            @error('brands.name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>

        <div class="form-group mb-3">
            <label class="form-label">Brand Logo</label>
            <input type="file" class="form-control" wire:model="image" accept="image/*">
            @error('image') <small class="text-danger">{{ $message }}</small> @enderror

            @if ($image)
                <div class="mt-2">
                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                </div>
            @elseif (isset($brands['image_path']) && $brands['image_path'])
                <div class="mt-2">
                    <img src="{{ asset('storage/' . $brands['image_path']) }}" alt="Current Logo" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                    <small class="text-muted d-block">Current logo</small>
                </div>
            @endif
        </div>

        <div class="mb-0">
            <label class="form-label fw-semibold mb-2 d-flex align-items-center">
                <i class="fa fa-globe me-2 text-primary"></i>
                Online Visibility
            </label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="brand_online_visibility_flag" wire:model="brands.online_visibility_flag">
                <label class="form-check-label" for="brand_online_visibility_flag">
                    <span class="fw-semibold">Visible Online</span>
                    <small class="text-muted d-block mt-1">
                        <i class="fa fa-info-circle me-1"></i>
                        When enabled, this brand and its products will be visible on the online platform
                    </small>
                </label>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-danger" wire:click="save(true)">Save & Close</button>
        <button class="btn btn-primary" wire:click="save(false)">Save</button>
    </div>
</div>

