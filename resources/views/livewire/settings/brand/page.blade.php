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
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-danger" wire:click="save(true)">Save & Close</button>
        <button class="btn btn-primary" wire:click="save(false)">Save</button>
    </div>
</div>

