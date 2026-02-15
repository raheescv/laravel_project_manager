<div class="modal" id="TailoringCustomerReceiptModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:150% !important">
        <div class="modal-content">
            @livewire('tailoring.customer-receipt')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleTailoringCustomerReceiptModal', event => {
            $('#TailoringCustomerReceiptModal').modal('toggle');
        });
    </script>
@endpush
