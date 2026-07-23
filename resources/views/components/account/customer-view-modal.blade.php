<div class="modal" id="CustomerViewModal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h1 class="modal-title fs-6">Customer Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @livewire('account.customer.view')
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCustomerViewModal', event => {
            $('#CustomerViewModal').modal('toggle');
        });
    </script>
@endpush
