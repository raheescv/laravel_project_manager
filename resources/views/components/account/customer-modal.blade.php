<div class="modal" id="CustomerModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('account.customer.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCustomerModal', event => {
            $('#CustomerModal').modal('toggle');
        });
    </script>
@endpush
