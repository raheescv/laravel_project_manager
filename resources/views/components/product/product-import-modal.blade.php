<div class="modal" id="ProductImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('product.import')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleProductImportModal', event => {
            $('#ProductImportModal').modal('toggle');
        });
    </script>
@endpush
