<div class="modal" id="CustomPaymentModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('sale.custom-payment')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCustomPaymentModal', event => {
            $('#CustomPaymentModal').modal('toggle');
        });
    </script>
@endpush
