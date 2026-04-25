<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">Purchase Configuration Settings</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="enable_barcode_print_after_submit">Enable Barcode Print After Submit</label>
                    {{ html()->select('enable_barcode_print_after_submit', ['yes' => 'Yes', 'no' => 'No'])->value('')->class('form-select form-select-sm')->placeholder('Select Option')->attribute('wire:model', 'enable_barcode_print_after_submit') }}
                    <small class="form-text text-muted">If enabled, barcode print page will open automatically after submitting a purchase.</small>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="default_quantity">Default Quantity</label>
                    {{ html()->input('number', 'default_quantity')->value('')->class('form-control form-control-sm')->attribute('step','0.001')->placeholder('Enter default quantity (e.g., 1)')->attribute('wire:model', 'default_quantity') }}
                    <small class="form-text text-muted">Default quantity when adding a new item to purchase.</small>
                </div>
                <div class="col-12 col-md-12">
                    <label class="form-label fw-medium small mb-1" for="purchase_item_row_mode">Same Product Cart Rows</label>
                    {{ html()->select('purchase_item_row_mode', ['merge' => 'Single Row (merge quantity)', 'separate' => 'Multiple Rows (add separately)'])->value('')->class('form-select form-select-sm')->placeholder('Choose how repeated product selection behaves')->attribute('wire:model', 'purchase_item_row_mode') }}
                    <small class="form-text text-muted">Controls whether selecting the same product again merges quantity or adds a separate purchase row.</small>
                </div>
                <div class="col-12 col-md-12" wire:ignore>
                    <label class="form-label fw-medium small mb-1" for="default_purchase_branch_id">Default Purchase Branch</label>
                    {{ html()->select('default_purchase_branch_id', $branches)->value($default_purchase_branch_id)->class('tomSelect')->id('default_purchase_branch_id')->multiple()->placeholder('Select Default Purchase Branch')->attribute('wire:model', 'default_purchase_branch_id') }}
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

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#default_purchase_branch_id').on('change', function() {
                const value = $(this).val() || null;
                @this.set('default_purchase_branch_id', value);
            });
        });
    </script>
@endpush

