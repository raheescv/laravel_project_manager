<div class="modal" id="InventoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('inventory.page')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleInventoryModal', event => {
            $('#InventoryModal').modal('toggle');
        });
    </script>
@endpush
