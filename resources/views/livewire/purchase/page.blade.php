<div>
    <!-- Vue Component Container -->
    <div id="purchase-page-vue" data-table-id="{{ $table_id }}" wire:ignore>
        <!-- Vue components will be mounted here -->
    </div>

    @push('scripts')
        @vite(['resources/js/purchase-page.js'])

        <!-- Keep existing select scripts for compatibility -->
        @include('components.select.vendorSelect')
        @include('components.select.productSelect')
        @include('components.select.paymentMethodSelect')

        <script>
            // Expose Livewire data to Vue component
            document.addEventListener('livewire:init', () => {
                window.purchasePageData = {
                    purchases: @json($purchases),
                    items: @json($items),
                    payments: @json($payments),
                    payment: @json($payment),
                    account_balance: {{ $account_balance ?? 0 }},
                    accounts: @json($accounts),
                    paymentMethods: @json($paymentMethods),
                    default_payment_method_id: {{ $default_payment_method_id ?? 1 }},
                    table_id: {{ $table_id ?? 'null' }},
                    canPrintPurchaseNote: @json(auth()->user()->can('purchase.purchase note print')),
                    canPrintBarcode: @json(auth()->user()->can('purchase.barcode print')),
                    canCancel: @json(auth()->user()->can('purchase.cancel'))
                }
            })

            // Listen for Livewire updates and sync to Vue
            document.addEventListener('livewire:update', () => {
                if (window.purchasePageVueInstance) {
                    // Trigger Vue component update
                    window.dispatchEvent(new CustomEvent('livewire-data-updated'))
                }
            })
        </script>
    @endpush
</div>
