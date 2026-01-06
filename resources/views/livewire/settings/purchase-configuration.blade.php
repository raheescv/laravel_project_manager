<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">Purchase Configuration Settings</h4>
                </div>
                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="enable_barcode_print_after_submit">Enable Barcode Print After Submit</label>
                                    {{ html()->select('enable_barcode_print_after_submit', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select')->placeholder('Select Option')->attribute('wire:model', 'enable_barcode_print_after_submit') }}
                                    <small class="form-text text-muted">If enabled, barcode print page will open automatically after submitting a purchase.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light text-end py-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


