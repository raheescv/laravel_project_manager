<div class="modal" id="SaleImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('sale.import')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleSaleImportModal', event => {
            $('#SaleImportModal').modal('toggle');
        });
    </script>
@endpush

