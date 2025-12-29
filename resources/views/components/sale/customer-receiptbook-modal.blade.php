<div class="modal" id="CustomerReceiptModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:150% !important%">
        <div class="modal-content ">
            @livewire('sale.customer-receiptbook')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCustomerReceiptModal', event => {
            $('#CustomerReceiptModal').modal('toggle');
        });
    </script>
@endpush
