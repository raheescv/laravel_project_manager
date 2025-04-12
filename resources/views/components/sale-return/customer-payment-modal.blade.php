<div class="modal" id="CustomerPaymentModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:150% !important%">
        <div class="modal-content ">
            @livewire('sale-return.customer-payment')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCustomerReceiptModal', event => {
            $('#CustomerPaymentModal').modal('toggle');
        });
    </script>
@endpush
