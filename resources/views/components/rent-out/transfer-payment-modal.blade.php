<div class="modal" id="TransferPaymentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.transfer-payment-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTransferPaymentModal', event => {
            $('#TransferPaymentModal').modal('toggle');
        });
    </script>
@endpush
