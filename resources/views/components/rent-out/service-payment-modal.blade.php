<div class="modal" id="ServicePaymentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.service-payment-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleServicePaymentModal', event => {
            $('#ServicePaymentModal').modal('toggle');
        });
    </script>
@endpush
