<div>
    <div class="modal-header">
        <h1 class="modal-title fs-5">
            <i class="fa fa-cubes text-primary me-2"></i>
            {{ $table_id ? 'Edit Raw Material' : 'Add Raw Material' }}
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form wire:submit="save">
        <div class="modal-body">
            @if ($this->getErrorBag()->count())
                <div class="alert alert-danger py-2 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach ($this->getErrorBag()->toArray() as $value)
                            <li class="small">{{ $value[0] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-3" wire:ignore>
                <label for="raw_material_select" class="form-label fw-medium">
                    Raw Material Product <span class="text-danger">*</span>
                </label>
                {{ html()->select('raw_material_select', [])->value('')->class('select-product_id-list')->attribute('type', 'product')->id('raw_material_select')->attribute('style', 'width:100%')->placeholder('Search and select product...') }}
                <small class="text-muted">Search by name, code or barcode.</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">
                    Quantity <span class="text-danger">*</span>
                </label>
                <input type="number" step="any" min="0.0001"
                    class="form-control @error('quantity') is-invalid @enderror"
                    placeholder="Enter quantity required"
                    wire:model="quantity">
                @error('quantity')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            @if (!$table_id)
                <button type="button" wire:click="save(false)" class="btn btn-success">Save & Add New</button>
            @endif
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save me-1"></i>
                {{ $table_id ? 'Update' : 'Save' }}
            </button>
        </div>
    </form>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#raw_material_select').on('change', function(e) {
                    var ts = document.querySelector('#raw_material_select')?.tomselect;
                    var id = e.target.value ? parseInt(e.target.value) : 0;
                    var name = '';
                    if (ts && id && ts.options[id]) {
                        name = ts.options[id].name || '';
                    }
                    @this.call('setRawMaterial', id, name);
                });
            });

            document.addEventListener('livewire:initialized', function() {
                Livewire.on('raw-material-modal-opened', function(params) {
                    var p = Array.isArray(params) ? (params[0] || {}) : (params || {});
                    setTimeout(function() {
                        var ts = document.querySelector('#raw_material_select')?.tomselect;
                        if (!ts) return;
                        if (p.rawMaterialId) {
                            ts.addOption({ id: p.rawMaterialId, name: p.rawMaterialName });
                            ts.setValue(p.rawMaterialId, true);
                        } else {
                            ts.clear(true);
                        }
                    }, 100);
                });
            });
        </script>
    @endpush
</div>
