<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Product Configuration Settings</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="barcode_type">Barcode Type</label>
                    {{ html()->select('barcode_type', barcodeTypes())->value('')->class('form-select form-select-sm')->placeholder('Select Barcode Type')->attribute('wire:model', 'barcode_type') }}
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="barcode_prefix">Barcode Prefix</label>
                    {{ html()->text('barcode_prefix')->value($barcode_prefix)->class('form-control form-control-sm')->placeholder('e.g. LS')->attribute('wire:model', 'barcode_prefix')->attribute('maxlength', '10') }}
                </div>
                <div class="col-12 col-md-12">
                    <label class="form-label fw-medium small mb-1" for="sync_barcode_to_code">Barcode and UPC/EAN/ISBN/SKU Equal</label>
                    {{ html()->select('sync_barcode_to_code', ['yes' => 'Yes', 'no' => 'No'])->value($sync_barcode_to_code)->class('form-select form-select-sm')->placeholder('Select Option')->attribute('wire:model', 'sync_barcode_to_code') }}
                    <small class="form-text text-muted">When enabled, Barcode value will be used for both Barcode and UPC/EAN/ISBN/SKU while creating products.</small>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light text-end py-2 px-3">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>
