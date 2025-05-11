<div class="modal" id="CustomerModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            @livewire('account.customer.page')
        </div>
    </div>
</div>
<x-settings.customer-type.customer-type-modal />
@push('scripts')
    <x-select.customerTypeSelect />
    <script>
        window.addEventListener('ToggleCustomerModal', event => {
            $('#CustomerModal').modal('toggle');
        });
    </script>
@endpush
