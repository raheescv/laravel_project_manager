<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0 text-white">System Configurations</h4>
                </div>
                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="barcode_type">Barcode Type</label>
                                    {{ html()->select('barcode_type', barcodeTypes())->value('')->class('form-select')->placeholder('Select Barcode Type')->attribute('wire:model', 'barcode_type') }}
                                </div>
                            </div>

                            <div class="col-12" wire:ignore>
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="payment_methods">Payment Methods</label>
                                    {{ html()->select('payment_methods', $paymentMethods)->value($payment_methods)->class('form-select select-account_id-list')->id('payment_methods')->multiple()->placeholder('Select Payment Methods')->attribute('wire:model', 'payment_methods') }}
                                </div>
                            </div>

                            <div class="col-12" wire:ignore>
                                <div class="form-group">
                                    <label class="form-label fw-medium" for="default_payment_method_id">Default Payment Method</label>
                                    {{ html()->select('default_payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('form-select select-account_id-list')->id('default_payment_method_id')->placeholder('Select Default Payment Method')->attribute('wire:model', 'default_payment_method_id') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3">
                        <button type="button" wire:click="dbView" class="btn btn-info btn-sm">
                            <i class="fa fa-database me-1"></i>View Table Re-Create
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
