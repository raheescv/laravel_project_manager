<div class="modal" id="ProductRawMaterialModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            @livewire('product.raw-material-form')
        </div>
    </div>
</div>
@push('scripts')
    <script>
        window.addEventListener('ToggleProductRawMaterialModal', event => {
            $('#ProductRawMaterialModal').modal('toggle');
        });
    </script>
@endpush
