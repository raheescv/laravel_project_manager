<div class="modal" id="ViewItemsModal" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="width:150% !important%">
        <div class="modal-content ">
            @livewire('sale.view-items')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleViewItemsModal', event => {
            $('#ViewItemsModal').modal('toggle');
        });
    </script>
@endpush
