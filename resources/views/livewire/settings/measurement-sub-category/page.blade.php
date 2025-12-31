<div>
    <div class="modal-header">
        <h5 class="modal-title">Measurement Subcategory</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <form wire:submit.prevent="save">
        <div class="modal-body">

            @if ($errors->any())
                <ul class="text-danger">
                    @foreach ($errors->all() as $error)
                        <li>* {{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <label>Measurement Category</label>
                    <select class="form-control"
                            wire:model="categories.measurement_category_id">
                        <option value="">-- Select Category --</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label>Model Name</label>
                    <input type="text"
                           class="form-control"
                           wire:model="categories.name">
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                Close
            </button>

            <button type="button" wire:click="save(true)" class="btn btn-success">
                Save & Add New
            </button>

            <button type="submit" class="btn btn-primary">
                Save
            </button>
        </div>
    </form>
</div>
