<div>
    <!-- Vue Component Container -->
    <div id="purchase-page-vue" data-table-id="{{ $table_id }}" wire:ignore>
        <!-- Vue components will be mounted here -->
    </div>

    {{-- Reads the vendor's own PDF or a photo of it into the cart above.
         Deliberately Livewire and outside the wire:ignore container: it is the
         one part of this page Vue does not own. --}}
    @include('livewire.purchase.partials.scan-invoice')

    @push('scripts')
        @vite(['resources/js/purchase-page.js'])

        <!-- Keep existing select scripts for compatibility -->
        @include('components.select.vendorSelect')
        @include('components.select.productSelect')
        @include('components.select.paymentMethodSelect')

        <script>
            // Expose Livewire data to Vue component (set immediately so it's available when Vue mounts)
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
                canCancel: @json(auth()->user()->can('purchase.cancel')),
                canScanInvoice: @json(auth()->user()->can('purchase.scan invoice'))
            }
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
