<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">System Configurations</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="barcode_type">Barcode Type</label>
                    {{ html()->select('barcode_type', barcodeTypes())->value('')->class('form-select form-select-sm')->placeholder('Select Barcode Type')->attribute('wire:model', 'barcode_type') }}
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="default_product_type">Default Product Type</label>
                    {{ html()->select('default_product_type', [
                        'product' => 'Products',
                        'service' => 'Services',
                        '' => 'All Types'
                    ])->value($default_product_type)->class('form-select form-select-sm')->placeholder('Select Default Product Type')->attribute('wire:model', 'default_product_type') }}
                </div>
                <div class="col-12 col-md-12" wire:ignore>
                    <label class="form-label fw-medium small mb-1" for="payment_methods">Payment Methods</label>
                    {{ html()->select('payment_methods', $paymentMethods)->value($payment_methods)->class('form-select select-account_id-list')->id('payment_methods')->multiple()->placeholder('Select Payment Methods')->attribute('wire:model', 'payment_methods') }}
                </div>
                <div class="col-12 col-md-12" wire:ignore>
                    <label class="form-label fw-medium small mb-1" for="default_payment_method_id">Default Payment Method</label>
                    {{ html()->select('default_payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('form-select select-account_id-list')->id('default_payment_method_id')->placeholder('Select Default Payment Method')->attribute('wire:model', 'default_payment_method_id') }}
                </div>
                <div class="col-12 col-md-12" wire:ignore>
                    <label class="form-label fw-medium small mb-1" for="default_purchase_branch_id">Default Purchase Branch</label>
                    {{ html()->select('default_purchase_branch_id', $branches)->value($default_purchase_branch_id)->class('form-select tomSelect')->id('default_purchase_branch_id')->multiple()->placeholder('Select Default Purchase Branch')->attribute('wire:model', 'default_purchase_branch_id') }}
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="country_id">Country</label>
                    {{ html()->select('country_id', $countries)->value($country_id)->class('form-select form-select-sm')->placeholder('Select Country')->attribute('wire:model', 'country_id') }}
                </div>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 px-3">
            <button type="button" wire:click="dbView" class="btn btn-info btn-sm">
                <i class="fa fa-database me-1"></i>View Table Re-Create
            </button>
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="fa fa-save me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#payment_methods').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('payment_methods', value);
            });

            $('#default_payment_method_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('default_payment_method_id', value);
            });

            $('#default_purchase_branch_id').on('change', function(e) {
                const value = $(this).val() || null;
                @this.set('default_purchase_branch_id', value);
            });

            window.addEventListener('SelectPaymentMethodDropDownValues', event => {
                const list = event.detail[0];
                const selected = event.detail[1];
                @this.set('payment_methods', selected);

                const tomSelectInstance = document.querySelector('#payment_methods').tomselect;
                tomSelectInstance.setValue(selected);

                if (selected) {
                    list.forEach(item => {
                        const preselectedData = {
                            id: @this.categories['parent_id'],
                            name: @this.categories['parent']['name'],
                        };
                        tomSelectInstance.addOption(preselectedData);
                        tomSelectInstance.addItem(preselectedData.id);
                    });
                } else {
                    tomSelectInstance.clear();
                }
            });
        });
    </script>
@endpush
