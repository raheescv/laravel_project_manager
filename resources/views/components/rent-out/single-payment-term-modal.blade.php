<div class="modal" id="SinglePaymentTermModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @livewire('rent-out.tabs.single-payment-term-modal')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleSinglePaymentTermModal', event => {
            $('#SinglePaymentTermModal').modal('toggle');
        });
    </script>
@endpush
