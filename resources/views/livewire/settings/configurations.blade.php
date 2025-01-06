<div>
    <div class="col-md-6">
        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <label for="barcode_type">Barcode Type</label>
                        {{ html()->select('barcode_type', barcodeTypes())->value('')->class('form-control')->placeholder('Select Any')->attribute('wire:model', 'barcode_type') }}
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12" wire:ignore>
                        <label for="payment_methods">Payment Methods</label>
                        {{ html()->select('payment_methods', $paymentMethods)->value($payment_methods)->class('select-account_id-list')->id('payment_methods')->multiple()->placeholder('Select Any')->attribute('wire:model', 'payment_methods') }}
                    </div>
                </div>
            </div>
            <div class="card-footer"> <br>
                <button type="submit" class="btn btn-primary">Save</button>
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
                window.addEventListener('SelectPaymentMethodDropDownValues', event => {
                    list = event.detail[0];
                    console.log(event.detail);
                    selected = event.detail[1];
                    @this.set('payment_methods', selected);
                    var tomSelectInstance = document.querySelector('#payment_methods').tomselect;
                    tomSelectInstance.setValue(selected);
                    if (selected) {
                        $.each(list, function(index, value) {
                            console.log(index);
                            console.log(value);
                            preselectedData = {
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
</div>
