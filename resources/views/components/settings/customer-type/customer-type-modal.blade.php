<div class="modal" id="CustomerTypeModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('settings.customer-type.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleCustomerTypeModal', event => {
            $('#CustomerTypeModal').modal('toggle');
        });
    </script>
@endpush
