<div class="modal" id="MultiplePaymentTermModal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.multiple-payment-term-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleMultiplePaymentTermModal', event => {
            $('#MultiplePaymentTermModal').modal('toggle');
        });
    </script>
@endpush
