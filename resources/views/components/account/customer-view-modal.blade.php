<div class="modal" id="CustomerViewModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('account.customer.view')
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
