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
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-danger" wire:click="save(true)">Save & Close</button>
        <button class="btn btn-primary" wire:click="save(false)">Save</button>
    </div>
</div>

