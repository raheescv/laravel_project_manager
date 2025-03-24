<div class="modal" id="VendorPaymentModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:150% !important%">
        <div class="modal-content ">
            @livewire('purchase.vendor-payment')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleVendorPaymentModal', event => {
            $('#VendorPaymentModal').modal('toggle');
        });
    </script>
@endpush
