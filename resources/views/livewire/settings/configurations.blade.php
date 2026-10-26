<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0 text-white">System Configurations</h5>
    </div>
    <form wire:submit="save">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-12 col-md-12" wire:ignore>
                    <label class="form-label fw-medium small mb-1" for="payment_methods">Payment Methods</label>
                    {{ html()->select('payment_methods', $paymentMethods)->value($payment_methods)->class('select-account_id-list')->id('payment_methods')->multiple()->placeholder('Select Payment Methods')->attribute('wire:model', 'payment_methods') }}
                </div>
                <div class="col-12 col-md-12" wire:ignore>
                    <label class="form-label fw-medium small mb-1" for="default_payment_method_id">Default Payment Method</label>
                    {{ html()->select('default_payment_method_id', $paymentMethods)->value($default_payment_method_id)->class('select-account_id-list')->id('default_payment_method_id')->placeholder('Select Default Payment Method')->attribute('wire:model', 'default_payment_method_id') }}
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium small mb-1">POS Payment Buttons</label>
                    <div class="row g-2">
                        @foreach (['Button 1', 'Button 2', 'Button 3 (optional)'] as $slot => $slotLabel)
                            <div class="col-12 col-md-4">
                                <label class="form-text mt-0 mb-1 d-block" for="pos_payment_button_{{ $slot }}">{{ $slotLabel }}</label>
                                @php($pickedIcon = $pos_payment_button_icons[$slot] ?? null)
                                <div class="input-group input-group-sm" wire:key="pos-payment-button-{{ $slot }}">
                                    <button type="button" class="btn btn-outline-secondary dropdown-toggle px-2" data-bs-toggle="dropdown" aria-expanded="false"
                                        title="{{ $pickedIcon ? 'Button icon' : 'Button icon — Auto' }}" aria-label="{{ $slotLabel }} icon">
                                        <i class="fa fa-{{ $this->buttonIcon($slot) }} fa-fw"></i>
                                    </button>
                                    <div class="dropdown-menu p-2" style="width: 13.5rem">
                                        <button type="button" wire:click="$set('pos_payment_button_icons.{{ $slot }}', null)"
                                            class="btn btn-sm w-100 mb-2 {{ $pickedIcon ? 'btn-outline-secondary' : 'btn-primary' }}">
                                            <i class="fa fa-magic me-1"></i>Auto
                                        </button>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach (posPaymentButtonIcons() as $iconKey => $iconLabel)
                                                <button type="button" wire:click="$set('pos_payment_button_icons.{{ $slot }}', '{{ $iconKey }}')" title="{{ $iconLabel }}" aria-label="{{ $iconLabel }}"
                                                    class="btn btn-sm {{ $pickedIcon === $iconKey ? 'btn-primary' : 'btn-outline-secondary' }}" style="width: 2.25rem">
                                                    <i class="fa fa-{{ $iconKey }} fa-fw"></i>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <select class="form-select" id="pos_payment_button_{{ $slot }}" wire:model.live="pos_payment_buttons.{{ $slot }}">
                                        <option value="">{{ ['Cash (default)', 'Card (default)', 'Not shown'][$slot] }}</option>
                                        @foreach ($posButtonOptions as $optionId => $optionName)
                                            <option value="{{ $optionId }}">{{ $optionName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-text">Shown on the POS checkout (web and app), followed by Credit and Custom. Every other payment method is picked from Custom.</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-medium small mb-1" for="country_id">Country</label>
                    {{ html()->select('country_id', $countries)->value($country_id)->class('form-select form-select-sm')->placeholder('Select Country')->attribute('wire:model', 'country_id') }}
                </div>
            </div>
        </div>
        <div class="card-footer bg-light d-flex justify-content-between align-items-center py-2 px-3">
            <div class="d-flex gap-2">
                <button type="button" wire:click="dbView" class="btn btn-info btn-sm">
                    <i class="fa fa-database me-1"></i>View Table Re-Create
                </button>
                <button type="button" wire:click="dbProcedure" class="btn btn-info btn-sm">
                    <i class="fa fa-cogs me-1"></i>Procedure Re-Create
                </button>
            </div>
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
