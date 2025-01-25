<div class="modal" id="EditItemModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('sale.edit-item')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleEditItemModal', event => {
            $('#EditItemModal').modal('toggle');
        });
    </script>
@endpush
