<div class="modal" id="EmployeeInventoryTransferModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            @livewire('employee-inventory.transfer')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleEmployeeInventoryTransferModal', event => {
            $('#EmployeeInventoryTransferModal').modal('toggle');
        });
    </script>
@endpush

