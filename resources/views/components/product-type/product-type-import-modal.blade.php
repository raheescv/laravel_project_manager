<div class="modal" id="ProductTypeImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            @livewire('product-type.import')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleProductTypeImportModal', event => {
            $('#ProductTypeImportModal').modal('toggle');
        });
    </script>
@endpush
