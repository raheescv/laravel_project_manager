<div class="modal" id="RoiPaymentModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.roi-payment-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleRoiPaymentModal', event => {
            $('#RoiPaymentModal').modal('toggle');
        });
    </script>
@endpush
